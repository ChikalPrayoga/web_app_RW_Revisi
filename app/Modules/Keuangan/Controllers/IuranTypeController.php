<?php

declare(strict_types=1);

namespace App\Modules\Keuangan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Keuangan\Resources\IuranTypeResource;
use App\Modules\Keuangan\Services\KeuanganService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller API untuk Master Jenis Iuran.
 *
 * @see docs/API_SPECIFICATION.md §3.6.1
 */
class IuranTypeController extends Controller
{
    public function __construct(
        protected KeuanganService $keuanganService
    ) {}

    /**
     * GET /api/v1/iuran-types
     *
     * Akses: Seluruh peran terautentikasi.
     */
    public function index(): JsonResponse
    {
        $types = $this->keuanganService->listIuranTypes();

        return response()->json([
            'success' => true,
            'message' => 'Data jenis iuran berhasil diambil',
            'data' => IuranTypeResource::collection($types),
        ], Response::HTTP_OK);
    }
}
