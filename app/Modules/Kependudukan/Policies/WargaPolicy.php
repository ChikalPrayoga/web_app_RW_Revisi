<?php

declare(strict_types=1);

namespace App\Modules\Kependudukan\Policies;

use App\Enums\RoleName;
use App\Models\User;
use App\Models\Warga;
use Illuminate\Auth\Access\HandlesAuthorization;

class WargaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the list of Warga.
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
     * Determine whether the user can view a specific Warga's detail.
     */
    public function view(User $user, Warga $warga): bool
    {
        if ($user->hasRole(RoleName::KETUA_RT->value)) {
            return $warga->kartuKeluarga !== null && $warga->kartuKeluarga->rt_code === $user->rt_code;
        }

        return $user->hasAnyRole([
            RoleName::SEKRETARIS_RW->value,
            RoleName::KETUA_RW->value,
            RoleName::SUPER_ADMIN->value,
        ]);
    }

    /**
     * Determine whether the user can create a Warga record.
     * Fine-grained area scoping (KK in own RT) is enforced in WargaService after KK is resolved.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            RoleName::KETUA_RT->value,
            RoleName::SEKRETARIS_RW->value,
            RoleName::SUPER_ADMIN->value,
        ]);
    }

    /**
     * Determine whether the user can update a Warga record.
     */
    public function update(User $user, Warga $warga): bool
    {
        if ($user->hasRole(RoleName::KETUA_RT->value)) {
            return $warga->kartuKeluarga !== null && $warga->kartuKeluarga->rt_code === $user->rt_code;
        }

        return $user->hasAnyRole([
            RoleName::SEKRETARIS_RW->value,
            RoleName::SUPER_ADMIN->value,
        ]);
    }

    /**
     * Determine whether the user can verify a Warga record.
     */
    public function verify(User $user, Warga $warga): bool
    {
        return $user->hasRole(RoleName::SEKRETARIS_RW->value);
    }
}
