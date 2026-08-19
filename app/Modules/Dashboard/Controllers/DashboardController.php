<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Dashboard\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller API untuk Modul Dashboard.
 *
 * Menangani endpoint REST API `GET /api/v1/dashboard/summary`
 * sesuai kontrak API_SPECIFICATION.md §3.8.1.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    /**
     * GET /api/v1/dashboard/summary
     * Mengambil ringkasan statistik dashboard sesuai peran pengguna yang login.
     * Area-scoped untuk Ketua RT. Ditolak untuk peran WARGA.
     *
     * @see API_SPECIFICATION.md §3.8.1
     * @see USER_STORIES.md US-DASH-01
     */
    public function summary(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Akses ditolak untuk role WARGA per API_SPECIFICATION.md §3.8.1
        if ($user->hasRole(RoleName::WARGA->value)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Peran Anda tidak memiliki izin untuk melihat data dashboard.',
            ], 403);
        }

        $data = $this->dashboardService->getSummary($user);

        return response()->json([
            'success' => true,
            'message' => 'Data dashboard berhasil diambil',
            'data' => $data,
        ], 200);
    }
}
