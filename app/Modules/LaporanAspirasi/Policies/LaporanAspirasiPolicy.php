<?php

declare(strict_types=1);

namespace App\Modules\LaporanAspirasi\Policies;

use App\Models\LaporanAspirasi;
use App\Models\User;

class LaporanAspirasiPolicy
{
    /**
     * Peran yang dapat melihat daftar laporan di panel pengurus.
     *
     * @see US-LAP-03, US-LAP-04
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['KETUA_RT', 'SEKRETARIS_RW', 'KETUA_RW', 'SUPER_ADMIN']);
    }

    /**
     * Peran yang dapat melihat detail laporan di panel pengurus.
     */
    public function view(User $user, LaporanAspirasi $laporan): bool
    {
        return $user->hasAnyRole(['KETUA_RT', 'SEKRETARIS_RW', 'KETUA_RW', 'SUPER_ADMIN']);
    }

    /**
     * Peran yang dapat mengubah status laporan (tindak lanjut).
     *
     * @see US-LAP-04 — pengurus RW termasuk Ketua RT, Sekretaris, Ketua RW
     */
    public function updateStatus(User $user, LaporanAspirasi $laporan): bool
    {
        // Status CLOSED bersifat final — tidak dapat diubah oleh siapapun
        if ($laporan->isClosed()) {
            return false;
        }

        return $user->hasAnyRole(['KETUA_RT', 'SEKRETARIS_RW', 'KETUA_RW']);
    }

    /**
     * Hanya Sekretaris RW, Ketua RW, dan Super Admin yang dapat menghapus laporan.
     */
    public function delete(User $user, LaporanAspirasi $laporan): bool
    {
        return $user->hasAnyRole(['SEKRETARIS_RW', 'KETUA_RW', 'SUPER_ADMIN']);
    }
}
