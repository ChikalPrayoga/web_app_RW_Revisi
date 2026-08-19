<?php

declare(strict_types=1);

namespace App\Modules\Keuangan\Policies;

use App\Enums\RoleName;
use App\Models\KasKeluar;
use App\Models\User;

class KasKeluarPolicy
{
    /**
     * Apakah user dapat melihat daftar pengeluaran kas RW.
     * Akses: BENDAHARA_RW, KETUA_RW, SUPER_ADMIN.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            RoleName::BENDAHARA_RW->value,
            RoleName::KETUA_RW->value,
            RoleName::SUPER_ADMIN->value,
        ]);
    }

    /**
     * Apakah user dapat melihat detail transaksi pengeluaran kas.
     * Akses: BENDAHARA_RW, KETUA_RW, SUPER_ADMIN.
     */
    public function view(User $user, KasKeluar $kasKeluar): bool
    {
        return $user->hasAnyRole([
            RoleName::BENDAHARA_RW->value,
            RoleName::KETUA_RW->value,
            RoleName::SUPER_ADMIN->value,
        ]);
    }

    /**
     * Apakah user dapat mencatat transaksi pengeluaran kas baru.
     * Akses: BENDAHARA_RW.
     * SUPER_ADMIN, KETUA_RT, KETUA_RW, WARGA dilarang melakukan mutasi pencatatan kas keluar.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(RoleName::BENDAHARA_RW->value);
    }

    /**
     * Apakah user dapat menyetujui atau menolak transaksi pengeluaran kas.
     * Akses: KETUA_RW (Dual-Control).
     * SUPER_ADMIN, KETUA_RT, BENDAHARA_RW, WARGA dilarang melakukan mutasi persetujuan kas keluar.
     * Catatan: Penegakan Anti Self-Approval (User tidak boleh menyetujui pencatatan sendiri).
     */
    public function approve(User $user, KasKeluar $kasKeluar): bool
    {
        if (! $user->hasRole(RoleName::KETUA_RW->value)) {
            return false;
        }

        // Anti Self-Approval
        if ($kasKeluar->recorded_by_user_id === $user->id) {
            return false;
        }

        return true;
    }
}
