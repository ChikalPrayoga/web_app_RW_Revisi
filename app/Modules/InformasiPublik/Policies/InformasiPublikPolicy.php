<?php

declare(strict_types=1);

namespace App\Modules\InformasiPublik\Policies;

use App\Enums\RoleName;
use App\Models\InformasiPublik;
use App\Models\User;

/**
 * Policy untuk mengontrol hak akses ke resource Informasi Publik.
 *
 * Publik/Guest dapat membaca konten berstatus PUBLISHED.
 * Pengelolaan konten (Create/Update/Delete/View Draft) dikhususkan untuk SEKRETARIS_RW, KETUA_RW, SUPER_ADMIN.
 *
 * @see docs/API_SPECIFICATION.md §3.7
 * @see docs/USER_STORIES.md §1.6
 */
class InformasiPublikPolicy
{
    /**
     * Apakah user/guest dapat melihat daftar informasi publik.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Apakah user/guest dapat melihat satu informasi publik tertentu.
     * Konten DRAFT atau ARCHIVED hanya dapat diakses oleh pengurus berwenang.
     */
    public function view(?User $user, InformasiPublik $informasi): bool
    {
        if ($informasi->isPublished()) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $user->hasAnyRole([
            RoleName::SEKRETARIS_RW->value,
            RoleName::KETUA_RW->value,
            RoleName::SUPER_ADMIN->value,
        ]);
    }

    /**
     * Apakah user dapat membuat konten informasi publik baru.
     * Akses: SEKRETARIS_RW, KETUA_RW, SUPER_ADMIN.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            RoleName::SEKRETARIS_RW->value,
            RoleName::KETUA_RW->value,
            RoleName::SUPER_ADMIN->value,
        ]);
    }

    /**
     * Apakah user dapat memperbarui konten informasi publik.
     * Akses: SEKRETARIS_RW, KETUA_RW, SUPER_ADMIN.
     */
    public function update(User $user, InformasiPublik $informasi): bool
    {
        return $user->hasAnyRole([
            RoleName::SEKRETARIS_RW->value,
            RoleName::KETUA_RW->value,
            RoleName::SUPER_ADMIN->value,
        ]);
    }

    /**
     * Apakah user dapat menghapus konten informasi publik (soft delete).
     * Akses: SEKRETARIS_RW, KETUA_RW, SUPER_ADMIN.
     */
    public function delete(User $user, InformasiPublik $informasi): bool
    {
        return $user->hasAnyRole([
            RoleName::SEKRETARIS_RW->value,
            RoleName::KETUA_RW->value,
            RoleName::SUPER_ADMIN->value,
        ]);
    }
}
