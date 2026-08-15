<?php

declare(strict_types=1);

use App\Modules\Auth\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — SIM Layanan Warga RW 047
|--------------------------------------------------------------------------
|
| Seluruh route di sini di-prefix dengan /api (oleh RouteServiceProvider)
| dan dilindungi middleware grup 'api'.
|
| Versi API: v1 (sesuai API_SPECIFICATION.md §1.2)
|
*/

Route::prefix('v1')->group(function (): void {

    // =========================================================================
    // Modul: Auth (API_SPECIFICATION.md §3.1)
    // =========================================================================
    Route::prefix('auth')->group(function (): void {

        // POST /api/v1/auth/login — Publik, dengan rate limiting 5x/menit per IP+email
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:login')
            ->name('auth.login');

        // POST /api/v1/auth/logout — Terautentikasi
        Route::post('logout', [AuthController::class, 'logout'])
            ->middleware(['auth:sanctum', 'active'])
            ->name('auth.logout');

        // GET /api/v1/auth/me — Terautentikasi
        Route::get('me', [AuthController::class, 'me'])
            ->middleware(['auth:sanctum', 'active'])
            ->name('auth.me');
    });
});
