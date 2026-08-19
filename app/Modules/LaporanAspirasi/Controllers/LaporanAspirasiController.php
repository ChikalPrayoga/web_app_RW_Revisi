<?php

declare(strict_types=1);

namespace App\Modules\LaporanAspirasi\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LaporanAspirasi;
use App\Modules\LaporanAspirasi\Requests\ListLaporanAspirasiRequest;
use App\Modules\LaporanAspirasi\Requests\StoreLaporanAspirasiRequest;
use App\Modules\LaporanAspirasi\Requests\UpdateStatusLaporanRequest;
use App\Modules\LaporanAspirasi\Resources\LaporanAspirasiResource;
use App\Modules\LaporanAspirasi\Services\LaporanAspirasiService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class LaporanAspirasiController extends Controller
{
    public function __construct(
        private readonly LaporanAspirasiService $service
    ) {}

    /**
     * POST /api/v1/laporan-aspirasi
     * Publik — tidak memerlukan autentikasi.
     */
    public function store(StoreLaporanAspirasiRequest $request): JsonResponse
    {
        $laporan = $this->service->submitLaporan($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil dikirim. Simpan nomor tiket Anda untuk memantau status.',
            'data' => [
                'aspirasi_id' => $laporan->aspirasi_id,
                'ticket_number' => $laporan->ticket_number,
                'current_status' => $laporan->current_status->value,
                'submitted_at' => $laporan->submitted_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * GET /api/v1/laporan-aspirasi/track/{ticket_number}
     * Publik — tracking menggunakan nomor tiket.
     */
    public function track(string $ticketNumber): JsonResponse
    {
        try {
            $laporan = $this->service->trackByTicket($ticketNumber);
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status laporan berhasil diambil.',
            'data' => new LaporanAspirasiResource($laporan),
        ]);
    }

    /**
     * GET /api/v1/laporan-aspirasi
     * Pengurus: KETUA_RT, SEKRETARIS_RW, KETUA_RW, SUPER_ADMIN.
     */
    public function index(ListLaporanAspirasiRequest $request): JsonResponse
    {
        $this->authorize('viewAny', LaporanAspirasi::class);

        $laporan = $this->service->listLaporan($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Daftar laporan berhasil diambil.',
            'data' => LaporanAspirasiResource::collection($laporan),
            'meta' => [
                'current_page' => $laporan->currentPage(),
                'per_page' => $laporan->perPage(),
                'total' => $laporan->total(),
                'last_page' => $laporan->lastPage(),
            ],
        ]);
    }

    /**
     * PATCH /api/v1/laporan-aspirasi/{id}/status
     * Pengurus: KETUA_RT, SEKRETARIS_RW, KETUA_RW.
     */
    public function updateStatus(UpdateStatusLaporanRequest $request, int $id): JsonResponse
    {
        $laporan = LaporanAspirasi::findOrFail($id);

        $this->authorize('updateStatus', $laporan);

        try {
            $updated = $this->service->updateStatus($laporan, $request->user(), $request->validated());
        } catch (UnprocessableEntityHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status laporan berhasil diperbarui.',
            'data' => new LaporanAspirasiResource($updated),
        ]);
    }

    /**
     * DELETE /api/v1/laporan-aspirasi/{id}
     * Pengurus: SEKRETARIS_RW, KETUA_RW, SUPER_ADMIN.
     */
    public function destroy(int $id): JsonResponse
    {
        $laporan = LaporanAspirasi::findOrFail($id);

        $this->authorize('delete', $laporan);

        $this->service->deleteLaporan($laporan, auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil dihapus.',
        ]);
    }
}
