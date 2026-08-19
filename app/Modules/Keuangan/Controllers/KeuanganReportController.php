<?php

declare(strict_types=1);

namespace App\Modules\Keuangan\Controllers;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Modules\Keuangan\Requests\RekapKeuanganRequest;
use App\Modules\Keuangan\Services\KeuanganService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller API untuk Laporan & Rekapitulasi Keuangan Gabungan RW.
 *
 * @see docs/API_SPECIFICATION.md §3.6.8
 */
class KeuanganReportController extends Controller
{
    public function __construct(
        protected KeuanganService $keuanganService
    ) {}

    /**
     * GET /api/v1/keuangan/rekapitulasi
     *
     * Akses: BENDAHARA_RW, KETUA_RW, SUPER_ADMIN.
     *
     * @see API_SPECIFICATION.md §3.6.8
     */
    public function rekapitulasi(RekapKeuanganRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasAnyRole([
            RoleName::BENDAHARA_RW->value,
            RoleName::KETUA_RW->value,
            RoleName::SUPER_ADMIN->value,
        ])) {
            throw new AccessDeniedHttpException('Anda tidak memiliki wewenang untuk mengakses rekapitulasi keuangan');
        }

        $data = $this->keuanganService->rekapGabungan($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Rekapitulasi keuangan gabungan berhasil diambil',
            'data' => $data,
        ], Response::HTTP_OK);
    }
}
