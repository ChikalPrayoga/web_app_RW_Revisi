<?php

declare(strict_types=1);

namespace App\Modules\Kependudukan\Policies;

use App\Enums\RoleName;
use App\Models\KartuKeluarga;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class KartuKeluargaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the list of Kartu Keluarga.
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
     * Determine whether the user can view a specific Kartu Keluarga.
     */
    public function view(User $user, KartuKeluarga $kk): bool
    {
        if ($user->hasRole(RoleName::KETUA_RT->value)) {
            return $kk->rt_code === $user->rt_code;
        }

        return $user->hasAnyRole([
            RoleName::SEKRETARIS_RW->value,
            RoleName::KETUA_RW->value,
            RoleName::SUPER_ADMIN->value,
        ]);
    }

    /**
     * Determine whether the user can create a Kartu Keluarga.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            RoleName::KETUA_RT->value,
            RoleName::SEKRETARIS_RW->value,
            RoleName::SUPER_ADMIN->value,
        ]);
    }
}
