<?php

declare(strict_types=1);

namespace App\Modules\Kependudukan\Services;

use App\Enums\RoleName;
use App\Enums\StatusWarga;
use App\Enums\VerificationDecision;
use App\Enums\VerificationStatus;
use App\Models\KartuKeluarga;
use App\Models\User;
use App\Models\Warga;
use App\Support\Audit\AuditService;
use App\Support\Security\DataEncryptionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class WargaService
{
    /**
     * Mengambil daftar data warga dengan filter & area scoping.
     *
     * @param  array<string, mixed>  $filters
     */
    public function listWarga(User $user, array $filters): LengthAwarePaginator
    {
        $query = Warga::query()->with('kartuKeluarga');

        // Area Scoping untuk KETUA_RT
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

        // Filter berdasarkan Kartu Keluarga tertentu via hash
        if (! empty($filters['no_kk_hash'])) {
            $noKkHash = (string) $filters['no_kk_hash'];
            $query->whereHas('kartuKeluarga', function ($q) use ($noKkHash): void {
                $q->where('no_kk_hash', $noKkHash);
            });
        }

        // Filter pencarian nama
        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where('nama_lengkap', 'ILIKE', "%{$search}%");
        }

        // Filter verification_status
        if (! empty($filters['verification_status'])) {
            $query->where('verification_status', $filters['verification_status']);
        }

        // Filter status_warga
        if (! empty($filters['status_warga'])) {
            $query->where('status_warga', $filters['status_warga']);
        }

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;
        $perPage = min(max($perPage, 1), 100);

        return $query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate($perPage);
    }

    /**
     * Menambahkan data warga baru ke dalam Kartu Keluarga.
     *
     * @param  array<string, mixed>  $data
     */
    public function createWarga(User $user, array $data, ?string $ipAddress = null): Warga
    {
        $nik = (string) $data['nik'];
        $nikHash = DataEncryptionService::deterministicHash($nik);

        // Pengecekan duplikasi NIK (409 Conflict)
        if (Warga::where('nik_hash', $nikHash)->exists()) {
            throw new ConflictHttpException('NIK sudah terdaftar pada sistem');
        }

        // Cari Kartu Keluarga induk via deterministic hash
        $noKk = (string) $data['no_kk'];
        $noKkHash = DataEncryptionService::deterministicHash($noKk);
        $kk = KartuKeluarga::where('no_kk_hash', $noKkHash)->first();

        if (! $kk) {
            throw ValidationException::withMessages([
                'no_kk' => ['Nomor KK tidak ditemukan'],
            ]);
        }

        // Area scoping KETUA_RT sudah ditegakkan oleh WargaPolicy::create() di Controller.
        // Pemeriksaan tambahan ini memastikan konsistensi bahkan jika Service dipanggil langsung.
        if ($user->hasRole(RoleName::KETUA_RT->value) && $kk->rt_code !== $user->rt_code) {
            abort(403, 'Anda hanya dapat menambahkan warga pada wilayah RT Anda');
        }

        return Warga::create([
            'kartu_keluarga_id' => $kk->id,
            'nik' => $nik,
            'nik_hash' => $nikHash,
            'no_kk' => $noKk,
            'nama_lengkap' => (string) $data['nama_lengkap'],
            'jenis_kelamin' => (string) $data['jenis_kelamin'],
            'tempat_lahir' => (string) $data['tempat_lahir'],
            'tanggal_lahir' => $data['tanggal_lahir'],
            'pekerjaan' => isset($data['pekerjaan']) ? (string) $data['pekerjaan'] : null,
            'nomor_hp' => isset($data['nomor_hp']) ? (string) $data['nomor_hp'] : null,
            'status_hubungan_keluarga' => (string) $data['status_hubungan_keluarga'],
            'status_sosio_ekonomi' => isset($data['status_sosio_ekonomi']) ? (string) $data['status_sosio_ekonomi'] : null,
            'status_warga' => $data['status_warga'] ?? StatusWarga::TETAP->value,
            'verification_status' => VerificationStatus::MENUNGGU_VERIFIKASI->value,
        ]);
    }

    /**
     * Catat audit trail akses detail warga (read action — tidak dicatat Observer).
     * Dipanggil dari Controller setelah Policy::view() sudah menyetujui akses.
     */
    public function logDetailView(User $user, Warga $warga, ?string $ipAddress = null): Warga
    {
        AuditService::log(
            module: 'Kependudukan',
            action: 'VIEW_WARGA_DETAIL',
            entityType: 'wargas',
            entityId: (string) $warga->id,
            userId: $user->id,
            ipAddress: $ipAddress
        );

        return $warga;
    }

    /**
     * Memperbarui data warga.
     * Model Warga sudah di-resolve dan diotorisasi via Policy::update() di Controller.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateWarga(User $user, Warga $warga, array $data): Warga
    {
        $warga->fill($data);

        // Jika ada perubahan status warga atau pengurus RT melakukan update, ubah status ke MENUNGGU_VERIFIKASI
        if ($user->hasRole(RoleName::KETUA_RT->value) || isset($data['status_warga'])) {
            $warga->verification_status = VerificationStatus::MENUNGGU_VERIFIKASI->value;
        }

        $warga->save();

        return $warga;
    }

    /**
     * Memverifikasi data warga (APPROVED atau REJECTED) oleh Sekretaris RW.
     * Model Warga sudah di-resolve dan diotorisasi via Policy::verify() di Controller.
     *
     * @param  array<string, mixed>  $data
     */
    public function verifyWarga(User $user, Warga $warga, array $data): Warga
    {
        if ($warga->verification_status !== VerificationStatus::MENUNGGU_VERIFIKASI->value) {
            throw new ConflictHttpException('Data warga ini tidak sedang dalam status menunggu verifikasi');
        }

        $decision = is_object($data['decision']) ? $data['decision']->value : (string) $data['decision'];
        if ($decision === VerificationDecision::APPROVED->value) {
            $warga->verification_status = VerificationStatus::TERVERIFIKASI->value;
            $warga->verification_notes = null;
            if (empty($warga->status_warga)) {
                $warga->status_warga = StatusWarga::TETAP->value;
            }
        } else {
            $warga->verification_status = VerificationStatus::DITOLAK->value;
            $warga->verification_notes = isset($data['rejection_notes']) ? (string) $data['rejection_notes'] : null;
        }

        $warga->verified_by_user_id = $user->id;
        $warga->save();

        return $warga;
    }
}
