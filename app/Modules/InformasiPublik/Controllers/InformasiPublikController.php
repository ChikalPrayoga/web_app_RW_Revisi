<?php

declare(strict_types=1);

namespace App\Modules\InformasiPublik\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InformasiPublik;
use App\Modules\InformasiPublik\Requests\ListInformasiPublikRequest;
use App\Modules\InformasiPublik\Requests\StoreInformasiPublikRequest;
use App\Modules\InformasiPublik\Requests\UpdateInformasiPublikRequest;
use App\Modules\InformasiPublik\Resources\InformasiPublikResource;
use App\Modules\InformasiPublik\Services\InformasiPublikService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller RESTful API untuk Modul Informasi Publik.
 *
 * @see docs/API_SPECIFICATION.md §3.7
 */
class InformasiPublikController extends Controller
{
    public function __construct(
        protected InformasiPublikService $informasiService
    ) {}

    /**
     * GET /api/v1/informasi-publik
     *
     * Akses: Publik.
     * Mengambil daftar konten pengumuman/berita/agenda yang berstatus PUBLISHED.
     *
     * @see API_SPECIFICATION.md §3.7.1
     */
    public function index(ListInformasiPublikRequest $request): JsonResponse
    {
        $paginator = $this->informasiService->listPublic($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Data informasi publik berhasil diambil',
            'data' => InformasiPublikResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * GET /api/v1/informasi-publik/{id}
     *
     * Akses: Publik.
     * Mengambil detail satu konten informasi yang berstatus PUBLISHED.
     */
    public function show(int $id): JsonResponse
    {
        $item = $this->informasiService->getPublicItem($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail informasi publik berhasil diambil',
            'data' => new InformasiPublikResource($item),
        ], Response::HTTP_OK);
    }

    /**
     * POST /api/v1/informasi-publik
     *
     * Akses: SEKRETARIS_RW, KETUA_RW, SUPER_ADMIN.
     * Membuat konten informasi publik baru.
     *
     * @see API_SPECIFICATION.md §3.7.2
     */
    public function store(StoreInformasiPublikRequest $request): JsonResponse
    {
        $this->authorize('create', InformasiPublik::class);

        $user = $request->user();
        $item = $this->informasiService->create($user, $request->validated(), $request->ip());

        return response()->json([
            'success' => true,
            'message' => 'Informasi publik berhasil dipublikasikan',
            'data' => [
                'id' => $item->id,
                'judul' => $item->judul,
                'status' => $item->status->value,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * PUT/PATCH /api/v1/informasi-publik/{id}
     *
     * Akses: SEKRETARIS_RW, KETUA_RW, SUPER_ADMIN.
     */
    public function update(UpdateInformasiPublikRequest $request, int $id): JsonResponse
    {
        $item = $this->informasiService->getItemForPengurus($id);
        $this->authorize('update', $item);

        $user = $request->user();
        $updated = $this->informasiService->update($user, $item, $request->validated(), $request->ip());

        return response()->json([
            'success' => true,
            'message' => 'Informasi publik berhasil diperbarui',
            'data' => new InformasiPublikResource($updated),
        ], Response::HTTP_OK);
    }

    /**
     * DELETE /api/v1/informasi-publik/{id}
     *
     * Akses: SEKRETARIS_RW, KETUA_RW, SUPER_ADMIN.
     */
    public function destroy(int $id): JsonResponse
    {
        $item = $this->informasiService->getItemForPengurus($id);
        $this->authorize('delete', $item);

        $user = request()->user();
        $this->informasiService->delete($user, $item, request()->ip());

        return response()->json([
            'success' => true,
            'message' => 'Informasi publik berhasil dihapus',
        ], Response::HTTP_OK);
    }
}
