<?php

declare(strict_types=1);

namespace App\Modules\Kependudukan\Services;

use App\Enums\RoleName;
use App\Models\KartuKeluarga;
use App\Models\User;
use App\Support\Security\DataEncryptionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class KartuKeluargaService
{
    /**
     * Mengambil daftar Kartu Keluarga dengan pagination & area scoping.
     *
     * @param  array<string, mixed>  $filters
     */
    public function listKartuKeluarga(User $user, array $filters): LengthAwarePaginator
    {
        $query = KartuKeluarga::query()->withCount('wargas');

        // Area Scoping untuk KETUA_RT
        if ($user->hasRole(RoleName::KETUA_RT->value)) {
            $query->where('rt_code', $user->rt_code);
        } elseif (! empty($filters['rt_code'])) {
            $query->where('rt_code', (string) $filters['rt_code']);
        }

        // Pencarian berdasarkan blok / nomor rumah
        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('blok', 'ILIKE', "%{$search}%")
                    ->orWhere('nomor_rumah', 'ILIKE', "%{$search}%")
                    ->orWhere('status_kepemilikan_rumah', 'ILIKE', "%{$search}%");
            });
        }

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;
        $perPage = min(max($perPage, 1), 100);

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Mendaftarkan Kartu Keluarga baru.
     *
     * @param  array<string, mixed>  $data
     */
    public function createKartuKeluarga(User $user, array $data, ?string $ipAddress = null): KartuKeluarga
    {
        // Penegakan area scoping untuk KETUA_RT
        if ($user->hasRole(RoleName::KETUA_RT->value)) {
            $data['rt_code'] = $user->rt_code;
        }

        $noKk = (string) $data['no_kk'];
        $noKkHash = DataEncryptionService::deterministicHash($noKk);

        // Pengecekan duplikasi No. KK (409 Conflict)
        if (KartuKeluarga::where('no_kk_hash', $noKkHash)->exists()) {
            throw new ConflictHttpException('Nomor Kartu Keluarga sudah terdaftar pada sistem');
        }

        return KartuKeluarga::create([
            'no_kk' => $noKk,
            'no_kk_hash' => $noKkHash,
            'rt_code' => $data['rt_code'],
            'alamat_lengkap' => $data['alamat_lengkap'],
            'blok' => $data['blok'] ?? null,
            'nomor_rumah' => $data['nomor_rumah'] ?? null,
            'status_kepemilikan_rumah' => $data['status_kepemilikan_rumah'],
        ]);
    }
}
