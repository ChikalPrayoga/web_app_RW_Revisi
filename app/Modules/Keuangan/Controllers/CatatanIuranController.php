<?php

declare(strict_types=1);

namespace App\Modules\Keuangan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CatatanIuran;
use App\Modules\Keuangan\Requests\ApproveCatatanIuranRequest;
use App\Modules\Keuangan\Requests\ListCatatanIuranRequest;
use App\Modules\Keuangan\Requests\RekapIuranRequest;
use App\Modules\Keuangan\Requests\StoreCatatanIuranRequest;
use App\Modules\Keuangan\Resources\CatatanIuranResource;
use App\Modules\Keuangan\Services\KeuanganService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller API untuk Transaksi Pencatatan Iuran Warga.
 *
 * @see docs/API_SPECIFICATION.md §3.6.2-§3.6.4
 */
class CatatanIuranController extends Controller
{
    public function __construct(
        protected KeuanganService $keuanganService
    ) {}

    /**
     * GET /api/v1/catatan-iuran
     *
     * Akses: KETUA_RT, BENDAHARA_RW, KETUA_RW, SUPER_ADMIN.
     */
    public function index(ListCatatanIuranRequest $request): JsonResponse
    {
        $this->authorize('viewAny', CatatanIuran::class);

        $user = $request->user();
        $paginator = $this->keuanganService->listCatatanIuran($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Daftar transaksi iuran berhasil diambil',
            'data' => CatatanIuranResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * POST /api/v1/catatan-iuran
     *
     * Akses: KETUA_RT.
     * Mencatat iuran warga (No. KK di-lookup via deterministic hash).
     *
     * @see API_SPECIFICATION.md §3.6.2
     */
    public function store(StoreCatatanIuranRequest $request): JsonResponse
    {
        $this->authorize('create', CatatanIuran::class);

        $user = $request->user();
        $catatan = $this->keuanganService->catatIuran($user, $request->validated(), $request->ip());

        return response()->json([
            'success' => true,
            'message' => 'Pencatatan iuran berhasil disimpan, menunggu persetujuan Bendahara RW',
            'data' => [
                'iuran_id' => $catatan->iuran_id,
                'no_kk_masked' => $catatan->kartuKeluarga?->no_kk_masked,
                'nominal' => (float) $catatan->nominal,
                'periode_bulan' => (int) $catatan->periode_bulan,
                'periode_tahun' => (int) $catatan->periode_tahun,
                'status' => $catatan->status->value,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * PATCH /api/v1/catatan-iuran/{id}/approve
     *
     * Akses: BENDAHARA_RW.
     * Menyetujui atau menolak transaksi iuran yang dicatat Ketua RT.
     *
     * @see API_SPECIFICATION.md §3.6.3
     */
    public function approve(ApproveCatatanIuranRequest $request, int $id): JsonResponse
    {
        $catatan = CatatanIuran::with('kartuKeluarga')->find($id);

        if (! $catatan) {
            throw new NotFoundHttpException('Data transaksi iuran tidak ditemukan');
        }

        $this->authorize('approve', $catatan);

        $user = $request->user();
        $validated = $request->validated();
        $catatan = $this->keuanganService->approveIuran($user, $catatan, $validated, $request->ip());

        $action = strtoupper((string) $validated['action']);
        $message = $action === 'APPROVE'
            ? 'Transaksi iuran berhasil disetujui'
            : 'Transaksi iuran berhasil ditolak';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'iuran_id' => $catatan->iuran_id,
                'status' => $catatan->status->value,
                'approved_by' => $catatan->approvedBy?->full_name,
                'approved_at' => $catatan->approved_at?->toISOString(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * GET /api/v1/catatan-iuran/rekapitulasi
     *
     * Akses: BENDAHARA_RW, KETUA_RW, SUPER_ADMIN.
     *
     * @see API_SPECIFICATION.md §3.6.4
     */
    public function rekapitulasi(RekapIuranRequest $request): JsonResponse
    {
        $this->authorize('viewAny', CatatanIuran::class);

        $data = $this->keuanganService->rekapIuran($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Rekapitulasi iuran berhasil diambil',
            'data' => $data,
        ], Response::HTTP_OK);
    }
}
