<?php

declare(strict_types=1);

namespace App\Modules\Keuangan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\KasKeluar;
use App\Modules\Keuangan\Requests\ApproveKasKeluarRequest;
use App\Modules\Keuangan\Requests\ListKasKeluarRequest;
use App\Modules\Keuangan\Requests\StoreKasKeluarRequest;
use App\Modules\Keuangan\Resources\KasKeluarResource;
use App\Modules\Keuangan\Services\KeuanganService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller API untuk Transaksi Pengeluaran Kas RW (Kas Keluar).
 *
 * @see docs/API_SPECIFICATION.md §3.6.5-§3.6.7
 */
class KasKeluarController extends Controller
{
    public function __construct(
        protected KeuanganService $keuanganService
    ) {}

    /**
     * GET /api/v1/kas-keluar
     *
     * Akses: BENDAHARA_RW, KETUA_RW, SUPER_ADMIN.
     *
     * @see API_SPECIFICATION.md §3.6.6
     */
    public function index(ListKasKeluarRequest $request): JsonResponse
    {
        $this->authorize('viewAny', KasKeluar::class);

        $user = $request->user();
        $paginator = $this->keuanganService->listKasKeluar($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengeluaran kas berhasil diambil',
            'data' => KasKeluarResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * POST /api/v1/kas-keluar
     *
     * Akses: BENDAHARA_RW.
     * Mencatat pengeluaran kas operasional RW (status PENDING).
     *
     * @see API_SPECIFICATION.md §3.6.5
     */
    public function store(StoreKasKeluarRequest $request): JsonResponse
    {
        $this->authorize('create', KasKeluar::class);

        $user = $request->user();
        $kasKeluar = $this->keuanganService->catatKasKeluar($user, $request->validated(), $request->ip());

        return response()->json([
            'success' => true,
            'message' => 'Pencatatan pengeluaran kas berhasil disimpan, menunggu persetujuan Ketua RW',
            'data' => [
                'id' => $kasKeluar->id,
                'kategori' => $kasKeluar->kategori,
                'keterangan' => $kasKeluar->keterangan,
                'nominal' => (float) $kasKeluar->nominal,
                'tanggal_pengeluaran' => $kasKeluar->tanggal_pengeluaran?->format('Y-m-d'),
                'status' => $kasKeluar->status->value,
                'recorded_by' => $kasKeluar->recordedBy?->full_name,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * PATCH /api/v1/kas-keluar/{id}/approve
     *
     * Akses: KETUA_RW.
     * Menyetujui atau menolak pengajuan pengeluaran kas (Dual-Control).
     *
     * @see API_SPECIFICATION.md §3.6.7
     */
    public function approve(ApproveKasKeluarRequest $request, int $id): JsonResponse
    {
        $kasKeluar = KasKeluar::find($id);

        if (! $kasKeluar) {
            throw new NotFoundHttpException('Data transaksi kas keluar tidak ditemukan');
        }

        $this->authorize('approve', $kasKeluar);

        $user = $request->user();
        $validated = $request->validated();
        $kasKeluar = $this->keuanganService->approveKasKeluar($user, $kasKeluar, $validated, $request->ip());

        $action = strtoupper((string) $validated['action']);
        $message = $action === 'APPROVE'
            ? 'Transaksi pengeluaran kas berhasil disetujui'
            : 'Transaksi pengeluaran kas berhasil ditolak';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'id' => $kasKeluar->id,
                'status' => $kasKeluar->status->value,
                'approved_by' => $kasKeluar->approvedBy?->full_name,
                'approved_at' => $kasKeluar->approved_at?->toISOString(),
            ],
        ], Response::HTTP_OK);
    }
}
