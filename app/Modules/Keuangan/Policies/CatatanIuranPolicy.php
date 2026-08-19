<?php

declare(strict_types=1);

namespace App\Modules\Keuangan\Policies;

use App\Enums\RoleName;
use App\Models\CatatanIuran;
use App\Models\User;

class CatatanIuranPolicy
{
    /**
     * Apakah user dapat melihat daftar transaksi iuran.
     * Akses: KETUA_RT, BENDAHARA_RW, KETUA_RW, SUPER_ADMIN.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            RoleName::KETUA_RT->value,
            RoleName::BENDAHARA_RW->value,
            RoleName::KETUA_RW->value,
            RoleName::SUPER_ADMIN->value,
        ]);
    }

    /**
     * Apakah user dapat melihat detail transaksi iuran tertentu.
     * KETUA_RT dibatasi ke wilayah RT-nya sendiri.
     */
    public function view(User $user, CatatanIuran $catatan): bool
    {
        if (! $user->hasAnyRole([
            RoleName::KETUA_RT->value,
            RoleName::BENDAHARA_RW->value,
            RoleName::KETUA_RW->value,
            RoleName::SUPER_ADMIN->value,
        ])) {
            return false;
        }

        if ($user->hasRole(RoleName::KETUA_RT->value)) {
            $rtCode = optional($catatan->kartuKeluarga)->rt_code;

            return $rtCode === $user->rt_code;
        }

        return true;
    }

    /**
     * Apakah user dapat mencatat transaksi iuran baru.
     * Akses: KETUA_RT (untuk warga wilayah RT-nya).
     * SUPER_ADMIN, BENDAHARA_RW, KETUA_RW, WARGA dilarang melakukan mutasi pencatatan iuran.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(RoleName::KETUA_RT->value);
    }

    /**
     * Apakah user dapat menyetujui atau menolak transaksi iuran.
     * Akses: BENDAHARA_RW (Dual-Control).
     * SUPER_ADMIN, KETUA_RT, KETUA_RW, WARGA dilarang melakukan mutasi persetujuan iuran.
     */
    public function approve(User $user, CatatanIuran $catatan): bool
    {
        return $user->hasRole(RoleName::BENDAHARA_RW->value);
    }
}
