<?php

declare(strict_types=1);

namespace App\Modules\Keuangan\Services;

use App\Enums\RoleName;
use App\Enums\StatusCatatanIuran;
use App\Enums\StatusKasKeluar;
use App\Models\CatatanIuran;
use App\Models\IuranType;
use App\Models\KartuKeluarga;
use App\Models\KasKeluar;
use App\Models\User;
use App\Support\Audit\AuditService;
use App\Support\Security\DataEncryptionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Service Layer untuk Modul Keuangan (Iuran Warga & Kas Keluar RW).
 *
 * Menegakkan seluruh aturan bisnis, validasi integritas finansial,
 * concurrency handling via Active Unique Constraint, area scoping RT,
 * anti self-approval, audit trail sanitasi PII, dan pelaporan rekapitulasi.
 *
 * @see docs/API_SPECIFICATION.md §3.6
 * @see docs/DATABASE_SCHEMA.md §3.9-§3.11
 * @see docs/USER_STORIES.md §1.5
 */
class KeuanganService
{
    /**
     * Nama-nama bulan dalam bahasa Indonesia.
     *
     * @var array<int, string>
     */
    private const INDONESIAN_MONTHS = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    /**
     * Mengambil daftar master jenis iuran yang aktif.
     *
     * @return Collection<int, IuranType>
     */
    public function listIuranTypes(): Collection
    {
        return IuranType::where('is_active', true)->orderBy('id')->get();
    }

    /**
     * Mengambil daftar transaksi pencatatan iuran dengan filter & area scoping.
     *
     * @param  array<string, mixed>  $filters
     */
    public function listCatatanIuran(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = CatatanIuran::query()->with(['kartuKeluarga', 'iuranType', 'recordedBy', 'approvedBy']);

        // Area Scoping untuk KETUA_RT — hanya iuran dari wilayah RT-nya
        if ($user->hasRole(RoleName::KETUA_RT->value)) {
            $userRt = $user->rt_code;
            $query->whereHas('kartuKeluarga', function ($q) use ($userRt): void {
                $q->where('rt_code', $userRt);
            });
        } elseif (! empty($filters['rt_code'])) {
            $rtCode = (string) $filters['rt_code'];
            $query->whereHas('kartuKeluarga', function ($q) use ($rtCode): void {
                $q->where('rt_code', $rtCode);
            });
        }

        // Filter status
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter jenis iuran
        if (! empty($filters['iuran_type_id'])) {
            $query->where('iuran_type_id', (int) $filters['iuran_type_id']);
        }

        // Filter periode bulan
        if (! empty($filters['periode_bulan'])) {
            $query->where('periode_bulan', (int) $filters['periode_bulan']);
        }

        // Filter periode tahun
        if (! empty($filters['periode_tahun'])) {
            $query->where('periode_tahun', (int) $filters['periode_tahun']);
        }

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;
        $perPage = min(max($perPage, 1), 100);

        return $query->orderByDesc('created_at')
            ->orderByDesc('iuran_id')
            ->paginate($perPage);
    }

    /**
     * Mencatat transaksi iuran baru oleh Ketua RT.
     *
     * Alur:
     * 1. Deterministic hash lookup no_kk -> kartu_keluargas.id (FK internal).
     * 2. Validasi kesesuaian wilayah RT pemohon ($kk->rt_code === $user->rt_code).
     * 3. Service pre-check duplikasi aktif (status != REJECTED).
     * 4. Atomic DB transaction + tangkap QueryException jika terjadi race condition.
     * 5. Pencatatan audit log tanpa membocorkan plaintext No. KK.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException|AccessDeniedHttpException|ConflictHttpException
     */
    public function catatIuran(User $user, array $data, ?string $ipAddress = null): CatatanIuran
    {
        $noKk = (string) $data['no_kk'];
        $noKkHash = DataEncryptionService::deterministicHash($noKk);

        $kartuKeluarga = KartuKeluarga::where('no_kk_hash', $noKkHash)->first();
        if (! $kartuKeluarga) {
            throw ValidationException::withMessages([
                'no_kk' => ['Nomor Kartu Keluarga tidak ditemukan dalam data kependudukan RW 047'],
            ]);
        }

        // Penegakan Area Scoping RT di Backend
        if ($user->hasRole(RoleName::KETUA_RT->value) && $kartuKeluarga->rt_code !== $user->rt_code) {
            throw new AccessDeniedHttpException('Anda hanya dapat mencatat iuran untuk warga di wilayah RT Anda (RT '.$user->rt_code.')');
        }

        $periodeBulan = (int) $data['periode_bulan'];
        $periodeTahun = (int) $data['periode_tahun'];
        $iuranTypeId = (int) $data['iuran_type_id'];
        $namaBulan = self::INDONESIAN_MONTHS[$periodeBulan] ?? (string) $periodeBulan;

        // Layer 1: Service Pre-Check Active Duplicate
        $existingActive = CatatanIuran::where('kartu_keluarga_id', $kartuKeluarga->id)
            ->where('iuran_type_id', $iuranTypeId)
            ->where('periode_bulan', $periodeBulan)
            ->where('periode_tahun', $periodeTahun)
            ->whereIn('status', [StatusCatatanIuran::PENDING->value, StatusCatatanIuran::APPROVED->value])
            ->exists();

        if ($existingActive) {
            throw new ConflictHttpException("Iuran untuk KK ini pada periode {$namaBulan} {$periodeTahun} sudah tercatat sebelumnya");
        }

        // Layer 2 & 3: Atomic DB Transaction + Database Unique Constraint Handling
        return DB::transaction(function () use ($user, $kartuKeluarga, $iuranTypeId, $periodeBulan, $periodeTahun, $data, $ipAddress, $namaBulan): CatatanIuran {
            try {
                $catatan = CatatanIuran::create([
                    'kartu_keluarga_id' => $kartuKeluarga->id,
                    'iuran_type_id' => $iuranTypeId,
                    'nominal' => $data['nominal'],
                    'periode_bulan' => $periodeBulan,
                    'periode_tahun' => $periodeTahun,
                    'tanggal_pembayaran' => $data['tanggal_pembayaran'] ?? now()->toDateString(),
                    'recorded_by_user_id' => $user->id,
                    'status' => StatusCatatanIuran::PENDING,
                    'payment_proof_path' => $data['payment_proof_path'] ?? null,
                ]);

                AuditService::log(
                    module: 'Keuangan',
                    action: 'CREATE_IURAN',
                    entityType: 'catatan_iurans',
                    entityId: (string) $catatan->iuran_id,
                    oldValues: null,
                    newValues: [
                        'iuran_id' => $catatan->iuran_id,
                        'iuran_type_id' => $iuranTypeId,
                        'nominal' => $catatan->nominal,
                        'periode_bulan' => $periodeBulan,
                        'periode_tahun' => $periodeTahun,
                        'status' => StatusCatatanIuran::PENDING->value,
                        'rt_code' => $kartuKeluarga->rt_code,
                    ],
                    userId: $user->id,
                    ipAddress: $ipAddress
                );

                return $catatan->load(['kartuKeluarga', 'iuranType', 'recordedBy']);
            } catch (QueryException $e) {
                // Menangkap kegagalan unique constraint dari database (final authority race condition)
                if (str_contains($e->getMessage(), 'uq_catatan_iuran_active') || str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'Duplicate entry')) {
                    throw new ConflictHttpException("Iuran untuk KK ini pada periode {$namaBulan} {$periodeTahun} sudah tercatat sebelumnya");
                }
                throw $e;
            }
        });
    }

    /**
     * Memproses persetujuan atau penolakan transaksi iuran oleh Bendahara RW.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ConflictHttpException|ValidationException
     */
    public function approveIuran(User $user, CatatanIuran $catatan, array $data, ?string $ipAddress = null): CatatanIuran
    {
        // Transaksi berstatus final tidak dapat diproses ulang
        if ($catatan->isFinal()) {
            throw new ConflictHttpException('Transaksi iuran ini sudah berstatus final ('.$catatan->status->value.') dan tidak dapat diproses ulang');
        }

        $action = strtoupper((string) $data['action']);
        if (! in_array($action, ['APPROVE', 'REJECT'], true)) {
            throw new InvalidArgumentException("Aksi tidak valid: {$action}");
        }

        if ($action === 'REJECT' && empty($data['rejection_notes'])) {
            throw ValidationException::withMessages([
                'rejection_notes' => ['Alasan penolakan wajib diisi ketika aksi REJECT'],
            ]);
        }

        return DB::transaction(function () use ($user, $catatan, $action, $data, $ipAddress): CatatanIuran {
            $oldStatus = $catatan->status->value;

            if ($action === 'APPROVE') {
                $catatan->status = StatusCatatanIuran::APPROVED;
                $catatan->approved_by_user_id = $user->id;
                $catatan->approved_at = now();
                $catatan->rejection_notes = null;
            } else {
                $catatan->status = StatusCatatanIuran::REJECTED;
                $catatan->approved_by_user_id = $user->id;
                $catatan->approved_at = now();
                $catatan->rejection_notes = (string) $data['rejection_notes'];
            }

            $catatan->save();

            AuditService::log(
                module: 'Keuangan',
                action: $action === 'APPROVE' ? 'APPROVE_IURAN' : 'REJECT_IURAN',
                entityType: 'catatan_iurans',
                entityId: (string) $catatan->iuran_id,
                oldValues: ['status' => $oldStatus],
                newValues: [
                    'status' => $catatan->status->value,
                    'approved_by_user_id' => $user->id,
                    'rejection_notes' => $catatan->rejection_notes,
                ],
                userId: $user->id,
                ipAddress: $ipAddress
            );

            return $catatan->load(['kartuKeluarga', 'iuranType', 'recordedBy', 'approvedBy']);
        });
    }

    /**
     * Mengambil daftar transaksi pengeluaran kas RW dengan pagination & filter.
     *
     * @param  array<string, mixed>  $filters
     */
    public function listKasKeluar(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = KasKeluar::query()->with(['recordedBy', 'approvedBy']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['kategori'])) {
            $query->where('kategori', 'LIKE', '%'.$filters['kategori'].'%');
        }

        if (! empty($filters['periode_bulan'])) {
            $query->whereMonth('tanggal_pengeluaran', (int) $filters['periode_bulan']);
        }

        if (! empty($filters['periode_tahun'])) {
            $query->whereYear('tanggal_pengeluaran', (int) $filters['periode_tahun']);
        }

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;
        $perPage = min(max($perPage, 1), 100);

        return $query->orderByDesc('tanggal_pengeluaran')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Mencatat transaksi pengeluaran kas RW baru oleh Bendahara RW.
     *
     * @param  array<string, mixed>  $data
     */
    public function catatKasKeluar(User $user, array $data, ?string $ipAddress = null): KasKeluar
    {
        return DB::transaction(function () use ($user, $data, $ipAddress): KasKeluar {
            $kasKeluar = KasKeluar::create([
                'kategori' => (string) $data['kategori'],
                'keterangan' => (string) $data['keterangan'],
                'nominal' => $data['nominal'],
                'tanggal_pengeluaran' => $data['tanggal_pengeluaran'],
                'bukti_path' => $data['bukti_path'] ?? null,
                'recorded_by_user_id' => $user->id,
                'status' => StatusKasKeluar::PENDING,
            ]);

            AuditService::log(
                module: 'Keuangan',
                action: 'CREATE_KAS_KELUAR',
                entityType: 'kas_keluars',
                entityId: (string) $kasKeluar->id,
                oldValues: null,
                newValues: [
                    'id' => $kasKeluar->id,
                    'kategori' => $kasKeluar->kategori,
                    'nominal' => $kasKeluar->nominal,
                    'tanggal_pengeluaran' => $kasKeluar->tanggal_pengeluaran?->toDateString(),
                    'status' => StatusKasKeluar::PENDING->value,
                ],
                userId: $user->id,
                ipAddress: $ipAddress
            );

            return $kasKeluar->load(['recordedBy']);
        });
    }

    /**
     * Memproses persetujuan atau penolakan pengeluaran kas RW oleh Ketua RW (Dual-Control).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ConflictHttpException|AccessDeniedHttpException|ValidationException
     */
    public function approveKasKeluar(User $user, KasKeluar $kasKeluar, array $data, ?string $ipAddress = null): KasKeluar
    {
        // Transaksi berstatus final tidak dapat diproses ulang
        if ($kasKeluar->isFinal()) {
            throw new ConflictHttpException('Transaksi kas keluar ini sudah berstatus final ('.$kasKeluar->status->value.') dan tidak dapat diproses ulang');
        }

        // Anti Self-Approval: Pencatat tidak boleh menyetujui transaksi pengeluaran sendiri
        if ($kasKeluar->recorded_by_user_id === $user->id) {
            throw new AccessDeniedHttpException('Pencatat kas keluar tidak boleh menyetujui transaksi sendiri (Dual-Control)');
        }

        $action = strtoupper((string) $data['action']);
        if (! in_array($action, ['APPROVE', 'REJECT'], true)) {
            throw new InvalidArgumentException("Aksi tidak valid: {$action}");
        }

        if ($action === 'REJECT' && empty($data['rejection_notes'])) {
            throw ValidationException::withMessages([
                'rejection_notes' => ['Alasan penolakan wajib diisi ketika aksi REJECT'],
            ]);
        }

        return DB::transaction(function () use ($user, $kasKeluar, $action, $data, $ipAddress): KasKeluar {
            $oldStatus = $kasKeluar->status->value;

            if ($action === 'APPROVE') {
                $kasKeluar->status = StatusKasKeluar::APPROVED;
                $kasKeluar->approved_by_user_id = $user->id;
                $kasKeluar->approved_at = now();
                $kasKeluar->rejection_notes = null;
            } else {
                $kasKeluar->status = StatusKasKeluar::REJECTED;
                $kasKeluar->approved_by_user_id = $user->id;
                $kasKeluar->approved_at = now();
                $kasKeluar->rejection_notes = (string) $data['rejection_notes'];
            }

            $kasKeluar->save();

            AuditService::log(
                module: 'Keuangan',
                action: $action === 'APPROVE' ? 'APPROVE_KAS_KELUAR' : 'REJECT_KAS_KELUAR',
                entityType: 'kas_keluars',
                entityId: (string) $kasKeluar->id,
                oldValues: ['status' => $oldStatus],
                newValues: [
                    'status' => $kasKeluar->status->value,
                    'approved_by_user_id' => $user->id,
                    'rejection_notes' => $kasKeluar->rejection_notes,
                ],
                userId: $user->id,
                ipAddress: $ipAddress
            );

            return $kasKeluar->load(['recordedBy', 'approvedBy']);
        });
    }

    /**
     * Mengambil rekapitulasi laporan penerimaan iuran warga (hanya APPROVED).
     * Sesuai API_SPECIFICATION.md §3.6.4.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function rekapIuran(array $filters): array
    {
        $bulan = (int) $filters['periode_bulan'];
        $tahun = (int) $filters['periode_tahun'];
        $rtCode = ! empty($filters['rt_code']) ? (string) $filters['rt_code'] : null;

        // Hitung total KK wajib bayar
        $kkQuery = KartuKeluarga::query();
        if ($rtCode) {
            $kkQuery->where('rt_code', $rtCode);
        }
        $totalKkWajibBayar = $kkQuery->count();

        // Hitung iuran APPROVED
        $iuranQuery = CatatanIuran::query()
            ->where('status', StatusCatatanIuran::APPROVED->value)
            ->where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun);

        if ($rtCode) {
            $iuranQuery->whereHas('kartuKeluarga', fn ($q) => $q->where('rt_code', $rtCode));
        }

        $totalKkSudahBayar = (clone $iuranQuery)->distinct('kartu_keluarga_id')->count('kartu_keluarga_id');
        $totalNominalTerkumpul = (float) (clone $iuranQuery)->sum('nominal');

        // Rincian per jenis iuran
        $activeTypes = IuranType::orderBy('id')->get();
        $rincian = [];
        foreach ($activeTypes as $type) {
            $typeNominal = (float) (clone $iuranQuery)
                ->where('iuran_type_id', $type->id)
                ->sum('nominal');

            $rincian[] = [
                'jenis_iuran' => $type->name,
                'total_nominal' => $typeNominal,
            ];
        }

        return [
            'periode' => sprintf('%04d-%02d', $tahun, $bulan),
            'total_kk_wajib_bayar' => $totalKkWajibBayar,
            'total_kk_sudah_bayar' => $totalKkSudahBayar,
            'total_nominal_terkumpul' => $totalNominalTerkumpul,
            'rincian_per_jenis_iuran' => $rincian,
        ];
    }

    /**
     * Mengambil rekapitulasi keuangan gabungan (Iuran APPROVED - Kas Keluar APPROVED).
     * Sesuai API_SPECIFICATION.md §3.6.8.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function rekapGabungan(array $filters): array
    {
        $bulan = (int) $filters['periode_bulan'];
        $tahun = (int) $filters['periode_tahun'];

        // Total Pemasukan dari Catatan Iuran APPROVED
        $iuranApproved = CatatanIuran::query()
            ->where('status', StatusCatatanIuran::APPROVED->value)
            ->where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun);

        $totalPemasukan = (float) (clone $iuranApproved)->sum('nominal');

        // Total Pengeluaran dari Kas Keluar APPROVED
        $kasApproved = KasKeluar::query()
            ->where('status', StatusKasKeluar::APPROVED->value)
            ->whereMonth('tanggal_pengeluaran', $bulan)
            ->whereYear('tanggal_pengeluaran', $tahun);

        $totalPengeluaran = (float) (clone $kasApproved)->sum('nominal');
        $saldoAkhir = $totalPemasukan - $totalPengeluaran;

        // Rincian pemasukan per jenis iuran
        $iuranTypes = IuranType::orderBy('id')->get();
        $rincianPemasukan = [];
        foreach ($iuranTypes as $type) {
            $typeQuery = (clone $iuranApproved)->where('iuran_type_id', $type->id);
            $nominal = (float) (clone $typeQuery)->sum('nominal');
            $count = (clone $typeQuery)->count();

            $rincianPemasukan[] = [
                'jenis_iuran' => $type->name,
                'code' => $type->code,
                'total_nominal' => $nominal,
                'jumlah_transaksi' => $count,
            ];
        }

        // Rincian pengeluaran per kategori
        $pengeluaranByKategori = (clone $kasApproved)
            ->select('kategori', DB::raw('SUM(nominal) as total_nominal'), DB::raw('COUNT(*) as jumlah_transaksi'))
            ->groupBy('kategori')
            ->get();

        $rincianPengeluaran = [];
        foreach ($pengeluaranByKategori as $item) {
            $rincianPengeluaran[] = [
                'kategori' => $item->kategori,
                'total_nominal' => (float) $item->total_nominal,
                'jumlah_transaksi' => (int) $item->jumlah_transaksi,
            ];
        }

        return [
            'periode' => sprintf('%04d-%02d', $tahun, $bulan),
            'total_pemasukan' => $totalPemasukan,
            'total_pengeluaran' => $totalPengeluaran,
            'saldo_akhir' => $saldoAkhir,
            'rincian_pemasukan_iuran' => $rincianPemasukan,
            'rincian_pengeluaran_kas' => $rincianPengeluaran,
        ];
    }
}
