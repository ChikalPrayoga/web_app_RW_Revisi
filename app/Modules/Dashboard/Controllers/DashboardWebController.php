<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Dashboard\Services\DashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Controller Web Blade untuk Modul Dashboard Pengurus & Warga.
 *
 * Mengikuti pola Layered Architecture: Web Controller tipis → memanggil DashboardService → merender Blade.
 * Tidak ada business logic di Controller atau Blade.
 */
class DashboardWebController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    /**
     * GET /dashboard
     * Menampilkan halaman Dashboard pengguna & pengurus RW/RT berbasis Blade Monolith.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $dashboardData = $this->dashboardService->getWebDashboardData($user);

        return view('dashboard', $dashboardData);
    }
}
