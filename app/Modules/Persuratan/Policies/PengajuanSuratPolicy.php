<?php

declare(strict_types=1);

namespace App\Modules\Persuratan\Policies;

use App\Enums\RoleName;
use App\Models\PengajuanSurat;
use App\Models\User;

/**
 * Policy untuk mengontrol akses ke resource PengajuanSurat.
 *
 * Menegakkan RBAC dan area scoping sesuai kewenangan peran.
 * Policy ini diregistrasi di AuthServiceProvider.
 *
 * @see AGENTS.md §1.2b — RBAC wajib ditegakkan di setiap endpoint
 * @see API_SPECIFICATION.md §3.4 — akses per endpoint Persuratan
 */
class PengajuanSuratPolicy
{
    /**
     * Apakah user dapat melihat daftar pengajuan surat.
     * Akses: KETUA_RT, SEKRETARIS_RW, KETUA_RW, SUPER_ADMIN.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            RoleName::KETUA_RT->value,
            RoleName::SEKRETARIS_RW->value,
            RoleName::KETUA_RW->value,
            RoleName::SUPER_ADMIN->value,
        ]);
    }

    /**
     * Apakah user dapat melihat detail satu pengajuan surat.
     * KETUA_RT dibatasi ke pengajuan dari wilayah RT-nya sendiri.
     */
    public function view(User $user, PengajuanSurat $pengajuan): bool
    {
        if (! $user->hasAnyRole([
            RoleName::KETUA_RT->value,
            RoleName::SEKRETARIS_RW->value,
            RoleName::KETUA_RW->value,
            RoleName::SUPER_ADMIN->value,
        ])) {
            return false;
        }

        // Area scoping untuk KETUA_RT — hanya RT miliknya
        if ($user->hasRole(RoleName::KETUA_RT->value)) {
            $rtCode = optional(optional($pengajuan->warga)->kartuKeluarga)->rt_code;

            return $rtCode === $user->rt_code;
        }

        return true;
    }

    /**
     * Apakah pengajuan surat baru dapat dibuat.
     * Pengajuan surat via Portal Warga adalah public self-service (tanpa login wajib).
     */
    public function create(?User $user): bool
    {
        return true;
    }

    /**
     * Apakah user dapat melakukan review/verifikasi pengajuan surat.
     * Akses: KETUA_RT, SEKRETARIS_RW, KETUA_RW.
     * Validasi tahap yang diperbolehkan per-role dilakukan di SuratService.
     */
    public function verify(User $user, PengajuanSurat $pengajuan): bool
    {
        if (! $user->hasAnyRole([
            RoleName::KETUA_RT->value,
            RoleName::SEKRETARIS_RW->value,
            RoleName::KETUA_RW->value,
        ])) {
            return false;
        }

        // Area scoping untuk KETUA_RT — hanya RT miliknya
        if ($user->hasRole(RoleName::KETUA_RT->value)) {
            $rtCode = optional(optional($pengajuan->warga)->kartuKeluarga)->rt_code;

            return $rtCode === $user->rt_code;
        }

        return true;
    }
}
