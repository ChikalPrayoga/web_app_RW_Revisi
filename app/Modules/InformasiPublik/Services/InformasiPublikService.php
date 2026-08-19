<?php

declare(strict_types=1);

namespace App\Modules\InformasiPublik\Services;

use App\Enums\KategoriInformasi;
use App\Enums\StatusInformasi;
use App\Models\InformasiPublik;
use App\Models\User;
use App\Support\Audit\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Service Layer untuk Modul Informasi Publik.
 *
 * Mengelola business logic untuk publikasi pengumuman, berita, dan agenda kegiatan RW 047.
 * Menegakkan pemisahan akses publik (hanya konten PUBLISHED) dan pengurus (CRUD draft/archived).
 *
 * @see docs/API_SPECIFICATION.md §3.7
 * @see docs/USER_STORIES.md §1.6
 */
class InformasiPublikService
{
    /**
     * Mengambil daftar konten informasi publik untuk warga/guest (hanya status PUBLISHED).
     *
     * @param  array<string, mixed>  $filters
     */
    public function listPublic(array $filters = []): LengthAwarePaginator
    {
        $query = InformasiPublik::query()
            ->published()
            ->with('publishedBy');

        if (! empty($filters['kategori'])) {
            $query->kategori($filters['kategori']);
        }

        if (! empty($filters['search'])) {
            $query->search((string) $filters['search']);
        }

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 10;
        $perPage = min(max($perPage, 1), 50);

        return $query->orderByDesc('tanggal_publikasi')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Mengambil daftar seluruh konten informasi untuk pengurus RW (termasuk DRAFT & ARCHIVED).
     *
     * @param  array<string, mixed>  $filters
     */
    public function listPengurus(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = InformasiPublik::query()->with('publishedBy');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['kategori'])) {
            $query->kategori($filters['kategori']);
        }

        if (! empty($filters['search'])) {
            $query->search((string) $filters['search']);
        }

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;
        $perPage = min(max($perPage, 1), 100);

        return $query->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Mengambil satu konten informasi yang berstatus PUBLISHED untuk publik.
     *
     * @throws NotFoundHttpException
     */
    public function getPublicItem(int $id): InformasiPublik
    {
        $item = InformasiPublik::query()
            ->published()
            ->with('publishedBy')
            ->find($id);

        if (! $item) {
            throw new NotFoundHttpException('Informasi publik tidak ditemukan');
        }

        return $item;
    }

    /**
     * Mengambil satu konten informasi untuk pengurus (tanpa batasan status publik).
     *
     * @throws NotFoundHttpException
     */
    public function getItemForPengurus(int $id): InformasiPublik
    {
        $item = InformasiPublik::query()
            ->with('publishedBy')
            ->find($id);

        if (! $item) {
            throw new NotFoundHttpException('Data informasi publik tidak ditemukan');
        }

        return $item;
    }

    /**
     * Mengambil konten terbaru yang berstatus PUBLISHED untuk beranda Portal Warga.
     *
     * @return Collection<int, InformasiPublik>
     */
    public function getLatestPublicContent(int $limit = 6): Collection
    {
        return InformasiPublik::query()
            ->published()
            ->with('publishedBy')
            ->orderByDesc('tanggal_publikasi')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Mengambil agenda kegiatan mendatang yang berstatus PUBLISHED.
     *
     * @return Collection<int, InformasiPublik>
     */
    public function getUpcomingAgendas(int $limit = 4): Collection
    {
        return InformasiPublik::query()
            ->published()
            ->kategori(KategoriInformasi::AGENDA)
            ->where(function ($q): void {
                $q->whereDate('tanggal_agenda', '>=', now()->toDateString())
                    ->orWhereNull('tanggal_agenda');
            })
            ->orderBy('tanggal_agenda')
            ->orderByDesc('tanggal_publikasi')
            ->limit($limit)
            ->get();
    }

    /**
     * Membuat konten informasi publik baru oleh pengurus.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data, ?string $ipAddress = null): InformasiPublik
    {
        return DB::transaction(function () use ($user, $data, $ipAddress): InformasiPublik {
            $status = isset($data['status'])
                ? (string) $data['status']
                : StatusInformasi::DRAFT->value;

            $tanggalPublikasi = ! empty($data['tanggal_publikasi'])
                ? $data['tanggal_publikasi']
                : now()->toDateString();

            $informasi = InformasiPublik::create([
                'judul' => $data['judul'],
                'konten' => $data['konten'],
                'kategori' => $data['kategori'],
                'tanggal_publikasi' => $tanggalPublikasi,
                'tanggal_agenda' => $data['tanggal_agenda'] ?? null,
                'published_by_user_id' => $user->id,
                'status' => $status,
            ]);

            AuditService::log(
                module: 'Informasi Publik',
                action: 'CREATE_INFORMASI_PUBLIK',
                entityType: 'informasi_publiks',
                entityId: (string) $informasi->id,
                oldValues: null,
                newValues: [
                    'id' => $informasi->id,
                    'judul' => $informasi->judul,
                    'kategori' => $informasi->kategori->value,
                    'status' => $informasi->status->value,
                    'tanggal_publikasi' => $informasi->tanggal_publikasi->toDateString(),
                ],
                userId: $user->id,
                ipAddress: $ipAddress
            );

            return $informasi->load('publishedBy');
        });
    }

    /**
     * Memperbarui konten informasi publik.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, InformasiPublik $informasi, array $data, ?string $ipAddress = null): InformasiPublik
    {
        return DB::transaction(function () use ($user, $informasi, $data, $ipAddress): InformasiPublik {
            $oldValues = [
                'judul' => $informasi->judul,
                'kategori' => $informasi->kategori->value,
                'status' => $informasi->status->value,
                'tanggal_publikasi' => $informasi->tanggal_publikasi?->toDateString(),
                'tanggal_agenda' => $informasi->tanggal_agenda?->toDateString(),
            ];

            $tanggalPublikasi = ! empty($data['tanggal_publikasi'])
                ? $data['tanggal_publikasi']
                : $informasi->tanggal_publikasi;

            $informasi->update([
                'judul' => $data['judul'],
                'konten' => $data['konten'],
                'kategori' => $data['kategori'],
                'tanggal_publikasi' => $tanggalPublikasi,
                'tanggal_agenda' => $data['tanggal_agenda'] ?? null,
                'status' => $data['status'],
            ]);

            AuditService::log(
                module: 'Informasi Publik',
                action: 'UPDATE_INFORMASI_PUBLIK',
                entityType: 'informasi_publiks',
                entityId: (string) $informasi->id,
                oldValues: $oldValues,
                newValues: [
                    'id' => $informasi->id,
                    'judul' => $informasi->judul,
                    'kategori' => $informasi->kategori->value,
                    'status' => $informasi->status->value,
                    'tanggal_publikasi' => $informasi->tanggal_publikasi?->toDateString(),
                    'tanggal_agenda' => $informasi->tanggal_agenda?->toDateString(),
                ],
                userId: $user->id,
                ipAddress: $ipAddress
            );

            return $informasi->fresh(['publishedBy']);
        });
    }

    /**
     * Menghapus konten informasi publik secara lunak (Soft Delete).
     */
    public function delete(User $user, InformasiPublik $informasi, ?string $ipAddress = null): bool
    {
        return DB::transaction(function () use ($user, $informasi, $ipAddress): bool {
            AuditService::log(
                module: 'Informasi Publik',
                action: 'DELETE_INFORMASI_PUBLIK',
                entityType: 'informasi_publiks',
                entityId: (string) $informasi->id,
                oldValues: [
                    'id' => $informasi->id,
                    'judul' => $informasi->judul,
                    'status' => $informasi->status->value,
                ],
                newValues: [
                    'deleted_at' => now()->toISOString(),
                ],
                userId: $user->id,
                ipAddress: $ipAddress
            );

            return (bool) $informasi->delete();
        });
    }

    /**
     * Statistik ringkasan informasi publik untuk portal warga.
     *
     * @return array<string, int>
     */
    public function getPublicStats(): array
    {
        return [
            'total_pengumuman' => InformasiPublik::published()->kategori(KategoriInformasi::PENGUMUMAN)->count(),
            'total_berita' => InformasiPublik::published()->kategori(KategoriInformasi::BERITA)->count(),
            'total_agenda' => InformasiPublik::published()->kategori(KategoriInformasi::AGENDA)->count(),
        ];
    }
}
