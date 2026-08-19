<?php

declare(strict_types=1);

namespace App\Modules\Persuratan\Services;

use App\Enums\JenisSurat;
use App\Enums\ReviewAction;
use App\Enums\RoleName;
use App\Enums\StatusPengajuanSurat;
use App\Models\PengajuanSurat;
use App\Models\User;
use App\Models\Warga;
use App\Support\Security\DataEncryptionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Service layer untuk Modul Persuratan.
 *
 * Menegakkan business logic alur verifikasi surat berjenjang RT → RW,
 * area scoping per rt_code melalui relasi Warga/KK, dan transisi status.
 * Controller memanggil Service ini — tidak ada logika bisnis di Controller.
 *
 * @see docs/API_SPECIFICATION.md §3.4
 * @see docs/DATABASE_SCHEMA.md §3.7
 */
class SuratService
{
    /**
     * Mengajukan permohonan surat baru via Portal Warga (Public Self-Service).
     *
     * Warga mengisi NIK, jenis_surat, dan keperluan tanpa perlu login.
     * Backend melakukan deterministic hash pada NIK dan lookup Warga terdaftar.
     * Record pengajuan menyimpan `warga_id` (FK fisik) — bukan NIK plaintext.
     * Tracking code di-generate otomatis (format: SRT-{YYYYMMDD}-{6char}).
     * Status awal: SUBMITTED.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException jika NIK tidak terdaftar dalam data kependudukan
     *
     * @see API_SPECIFICATION.md §3.4.1
     * @see USER_STORIES.md US-SRT-01
     */
    public function submitPengajuan(array $data): PengajuanSurat
    {
        $nik = (string) $data['nik'];
        $nikHash = DataEncryptionService::deterministicHash($nik);

        $warga = Warga::where('nik_hash', $nikHash)->first();

        if (! $warga) {
            throw ValidationException::withMessages([
                'nik' => ['NIK tidak terdaftar dalam data kependudukan RW 047'],
            ]);
        }

        return DB::transaction(function () use ($warga, $data): PengajuanSurat {
            $trackingCode = $this->generateTrackingCode();

            $jenisSurat = is_object($data['jenis_surat'])
                ? $data['jenis_surat']->value
                : (string) $data['jenis_surat'];

            return PengajuanSurat::create([
                'tracking_code' => $trackingCode,
                'warga_id' => $warga->id,
                'jenis_surat' => $jenisSurat,
                'keperluan' => (string) $data['keperluan'],
                'current_status' => StatusPengajuanSurat::SUBMITTED->value,
                'tanggal_pengajuan' => now(),
            ]);
        });
    }

    /**
     * Melacak status pengajuan surat menggunakan kode pelacakan publik.
     *
     * Dapat diakses tanpa autentikasi — hanya mengembalikan informasi non-sensitif.
     * NIK/data warga tidak diekspos dalam response tracking ini.
     * Jika status REJECTED, catatan_penolakan dapat dibaca oleh pemohon.
     *
     * @see API_SPECIFICATION.md §3.4.2
     * @see USER_STORIES.md US-SRT-02
     */
    public function trackByKode(string $trackingCode): PengajuanSurat
    {
        $pengajuan = PengajuanSurat::where('tracking_code', $trackingCode)->first();

        if (! $pengajuan) {
            throw new NotFoundHttpException('Kode pelacakan tidak ditemukan');
        }

        return $pengajuan;
    }

    /**
     * Mengambil daftar pengajuan surat untuk keperluan verifikasi pengurus (area-scoped).
     *
     * Area scoping untuk KETUA_RT: hanya pengajuan dari warga di RT miliknya
     * melalui relasi data warga -> kartuKeluarga -> rt_code.
     * Scoping ditegakkan di Service Layer (bukan hanya di Controller/Policy).
     *
     * @param  array<string, mixed>  $filters
     *
     * @see API_SPECIFICATION.md §3.4.3
     * @see USER_STORIES.md US-SRT-03
     */
    public function listPengajuan(User $user, array $filters): LengthAwarePaginator
    {
        $query = PengajuanSurat::query()->with(['warga.kartuKeluarga']);

        // Area Scoping untuk KETUA_RT — lihat AGENTS.md §1.2b dan RULES.md §1.2
        if ($user->hasRole(RoleName::KETUA_RT->value)) {
            $userRt = $user->rt_code;
            $query->whereHas('warga.kartuKeluarga', function ($q) use ($userRt): void {
                $q->where('rt_code', $userRt);
            });
        } elseif (! empty($filters['rt_code'])) {
            $rtCode = (string) $filters['rt_code'];
            $query->whereHas('warga.kartuKeluarga', function ($q) use ($rtCode): void {
                $q->where('rt_code', $rtCode);
            });
        }

        // Filter berdasarkan status
        if (! empty($filters['current_status'])) {
            $query->where('current_status', $filters['current_status']);
        }

        // Filter berdasarkan jenis surat
        if (! empty($filters['jenis_surat'])) {
            $query->where('jenis_surat', $filters['jenis_surat']);
        }

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;
        $perPage = min(max($perPage, 1), 100);

        return $query->orderBy('tanggal_pengajuan', 'desc')
            ->orderBy('pengajuan_id', 'desc')
            ->paginate($perPage);
    }

    /**
     * Review pengajuan surat oleh Ketua RT (SUBMITTED → RT_REVIEW atau REJECTED).
     *
     * Ketua RT hanya berwenang memproses pengajuan dari warga di wilayah RT-nya.
     * Transisi status: SUBMITTED → RT_REVIEW (approve) atau SUBMITTED → REJECTED (reject).
     * Saat ditolak, alasan disimpan di `catatan_penolakan` dan tanggal_selesai dicatat.
     * Aksi pada status selain SUBMITTED oleh RT ditolak dengan 409 Conflict.
     *
     * @param  array<string, mixed>  $data
     *
     * @see API_SPECIFICATION.md §3.4.4
     * @see USER_STORIES.md US-SRT-04
     */
    public function reviewByRt(User $user, PengajuanSurat $pengajuan, array $data): PengajuanSurat
    {
        // Hanya dapat me-review saat masih berstatus SUBMITTED
        if ($pengajuan->current_status !== StatusPengajuanSurat::SUBMITTED) {
            throw new ConflictHttpException(
                'Pengajuan ini tidak sedang menunggu review Ketua RT (status saat ini: '.$pengajuan->current_status->value.')'
            );
        }

        return DB::transaction(function () use ($pengajuan, $data): PengajuanSurat {
            $action = is_object($data['action']) ? $data['action'] : ReviewAction::from((string) $data['action']);

            if ($action === ReviewAction::APPROVE) {
                $pengajuan->current_status = StatusPengajuanSurat::RT_REVIEW;
            } else {
                // Penolakan RT menghentikan alur, status REJECTED final dengan catatan penolakan
                $pengajuan->current_status = StatusPengajuanSurat::REJECTED;
                $pengajuan->catatan_penolakan = isset($data['catatan']) ? (string) $data['catatan'] : null;
                $pengajuan->tanggal_selesai = now();
            }

            $pengajuan->save();

            return $pengajuan;
        });
    }

    /**
     * Verifikasi akhir pengajuan surat oleh Sekretaris/Ketua RW.
     *
     * Tahap RT_REVIEW → RW_REVIEW (Sekretaris forward ke Ketua RW) atau COMPLETED/REJECTED.
     * Transisi final: RW_REVIEW → COMPLETED (approve) atau RW_REVIEW → REJECTED (reject).
     * `nomor_surat` diterbitkan otomatis saat status menjadi COMPLETED.
     * Saat ditolak, alasan disimpan di `catatan_penolakan` dan tanggal_selesai dicatat.
     *
     * @param  array<string, mixed>  $data
     *
     * @see API_SPECIFICATION.md §3.4.4
     * @see USER_STORIES.md US-SRT-05
     */
    public function verifyByRw(User $user, PengajuanSurat $pengajuan, array $data): PengajuanSurat
    {
        // RW hanya dapat memverifikasi saat status RT_REVIEW atau RW_REVIEW
        $validStatuses = [StatusPengajuanSurat::RT_REVIEW, StatusPengajuanSurat::RW_REVIEW];
        if (! in_array($pengajuan->current_status, $validStatuses, true)) {
            throw new ConflictHttpException(
                'Pengajuan ini tidak sedang menunggu verifikasi RW (status saat ini: '.$pengajuan->current_status->value.')'
            );
        }

        return DB::transaction(function () use ($user, $pengajuan, $data): PengajuanSurat {
            $action = is_object($data['action']) ? $data['action'] : ReviewAction::from((string) $data['action']);

            if ($action === ReviewAction::APPROVE) {
                // Sekretaris RW meneruskan ke Ketua RW, atau Ketua RW menyelesaikan
                if ($user->hasRole(RoleName::SEKRETARIS_RW->value)
                    && $pengajuan->current_status === StatusPengajuanSurat::RT_REVIEW
                ) {
                    $pengajuan->current_status = StatusPengajuanSurat::RW_REVIEW;
                } else {
                    // Ketua RW approve final — terbitkan nomor surat
                    $pengajuan->current_status = StatusPengajuanSurat::COMPLETED;
                    $pengajuan->nomor_surat = $this->generateNomorSurat($pengajuan);
                    $pengajuan->tanggal_selesai = now();
                }
            } else {
                // Penolakan RW bersifat final dengan catatan penolakan
                $pengajuan->current_status = StatusPengajuanSurat::REJECTED;
                $pengajuan->catatan_penolakan = isset($data['catatan']) ? (string) $data['catatan'] : null;
                $pengajuan->tanggal_selesai = now();
            }

            $pengajuan->save();

            return $pengajuan;
        });
    }

    /**
     * Generate tracking code unik untuk pengajuan surat baru.
     *
     * Format: SRT-{YYYYMMDD}-{6char uppercase random string}
     * Contoh: SRT-20260817-A8F3K2
     */
    private function generateTrackingCode(): string
    {
        do {
            $code = 'SRT-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (PengajuanSurat::where('tracking_code', $code)->exists());

        return $code;
    }

    /**
     * Generate nomor surat resmi saat pengajuan berstatus COMPLETED.
     *
     * Format: {nomor_urut}/SURAT/{bulan}/{tahun}
     * Nomor urut dihitung dari total surat COMPLETED pada bulan/tahun berjalan.
     */
    private function generateNomorSurat(PengajuanSurat $pengajuan): string
    {
        $bulan = now()->format('m');
        $tahun = now()->format('Y');

        $nomorUrut = PengajuanSurat::where('current_status', StatusPengajuanSurat::COMPLETED->value)
            ->whereYear('tanggal_selesai', $tahun)
            ->whereMonth('tanggal_selesai', $bulan)
            ->count() + 1;

        $jenisLabel = $pengajuan->jenis_surat === JenisSurat::SURAT_PENGANTAR ? 'SP' : 'SKD';

        return sprintf('%03d/%s/RW047/%s/%s', $nomorUrut, $jenisLabel, $bulan, $tahun);
    }
}
