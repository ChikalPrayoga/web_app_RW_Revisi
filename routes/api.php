<?php

declare(strict_types=1);

use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Kependudukan\Controllers\KartuKeluargaController;
use App\Modules\Kependudukan\Controllers\WargaController;
use App\Modules\Keuangan\Controllers\CatatanIuranController;
use App\Modules\Keuangan\Controllers\IuranTypeController;
use App\Modules\Keuangan\Controllers\KasKeluarController;
use App\Modules\Keuangan\Controllers\KeuanganReportController;
use App\Modules\Persuratan\Controllers\PengajuanSuratController;
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

    // =========================================================================
    // Modul: Kependudukan (API_SPECIFICATION.md §3.3)
    // =========================================================================
    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {

        // Kartu Keluarga
        Route::get('kartu-keluarga', [KartuKeluargaController::class, 'index'])->name('kartu-keluarga.index');
        Route::post('kartu-keluarga', [KartuKeluargaController::class, 'store'])->name('kartu-keluarga.store');

        // Warga
        Route::get('warga', [WargaController::class, 'index'])->name('warga.index');
        Route::post('warga', [WargaController::class, 'store'])->name('warga.store');
        Route::get('warga/{nik_hash}', [WargaController::class, 'show'])->name('warga.show');
        Route::patch('warga/{nik_hash}', [WargaController::class, 'update'])->name('warga.update');
        Route::patch('warga/{nik_hash}/verify', [WargaController::class, 'verify'])->name('warga.verify');
    });

    // =========================================================================
    // Modul: Persuratan (API_SPECIFICATION.md §3.4)
    // =========================================================================
    Route::prefix('surat')->name('surat.')->group(function (): void {

        // Public endpoints — tidak memerlukan autentikasi
        // POST /api/v1/surat/pengajuan — Submit pengajuan baru
        Route::post('pengajuan', [PengajuanSuratController::class, 'store'])
            ->name('pengajuan.store');

        // GET /api/v1/surat/pengajuan/track/{tracking_code} — Public tracking
        // PENTING: route track HARUS sebelum route {id} agar tidak terjadi konflik
        Route::get('pengajuan/track/{tracking_code}', [PengajuanSuratController::class, 'track'])
            ->name('pengajuan.track');

        // Protected endpoints — memerlukan autentikasi dan RBAC
        Route::middleware(['auth:sanctum', 'active'])->group(function (): void {

            // GET /api/v1/surat/pengajuan — Daftar pengajuan (KETUA_RT, SEKRETARIS_RW, KETUA_RW, SUPER_ADMIN)
            Route::get('pengajuan', [PengajuanSuratController::class, 'index'])
                ->name('pengajuan.index');

            // POST /api/v1/surat/pengajuan/{id}/verify — Review/Verifikasi
            Route::post('pengajuan/{id}/verify', [PengajuanSuratController::class, 'verify'])
                ->name('pengajuan.verify');
        });
    });

    // =========================================================================
    // Modul: Keuangan (API_SPECIFICATION.md §3.6)
    // =========================================================================
    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {

        // Master Jenis Iuran (3.6.1)
        Route::get('iuran-types', [IuranTypeController::class, 'index'])->name('iuran-types.index');

        // Pencatatan Iuran Warga (3.6.2 - 3.6.4)
        Route::get('catatan-iuran', [CatatanIuranController::class, 'index'])->name('catatan-iuran.index');
        Route::post('catatan-iuran', [CatatanIuranController::class, 'store'])->name('catatan-iuran.store');
        Route::patch('catatan-iuran/{id}/approve', [CatatanIuranController::class, 'approve'])->name('catatan-iuran.approve');
        Route::get('catatan-iuran/rekapitulasi', [CatatanIuranController::class, 'rekapitulasi'])->name('catatan-iuran.rekapitulasi');

        // Pengeluaran Kas RW (3.6.5 - 3.6.7)
        Route::get('kas-keluar', [KasKeluarController::class, 'index'])->name('kas-keluar.index');
        Route::post('kas-keluar', [KasKeluarController::class, 'store'])->name('kas-keluar.store');
        Route::patch('kas-keluar/{id}/approve', [KasKeluarController::class, 'approve'])->name('kas-keluar.approve');

        // Rekapitulasi Keuangan Gabungan (3.6.8)
        Route::get('keuangan/rekapitulasi', [KeuanganReportController::class, 'rekapitulasi'])->name('keuangan.rekapitulasi');
    });

    // =========================================================================
    // Modul: Informasi Publik (API_SPECIFICATION.md §3.7)
    // =========================================================================
    // Public endpoints (tidak memerlukan login)
    Route::get('informasi-publik', [\App\Modules\InformasiPublik\Controllers\InformasiPublikController::class, 'index'])->name('informasi-publik.index');
    Route::get('informasi-publik/{id}', [\App\Modules\InformasiPublik\Controllers\InformasiPublikController::class, 'show'])->name('informasi-publik.show');

    // Protected mutation endpoints (SEKRETARIS_RW, KETUA_RW, SUPER_ADMIN)
    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        Route::post('informasi-publik', [\App\Modules\InformasiPublik\Controllers\InformasiPublikController::class, 'store'])->name('informasi-publik.store');
        Route::match(['put', 'patch'], 'informasi-publik/{id}', [\App\Modules\InformasiPublik\Controllers\InformasiPublikController::class, 'update'])->name('informasi-publik.update');
        Route::delete('informasi-publik/{id}', [\App\Modules\InformasiPublik\Controllers\InformasiPublikController::class, 'destroy'])->name('informasi-publik.destroy');
    });

    // =========================================================================
    // Modul: Laporan & Aspirasi (API_SPECIFICATION.md §3.5)
    // =========================================================================
    // Public endpoints — tidak memerlukan autentikasi
    Route::post('laporan-aspirasi', [\App\Modules\LaporanAspirasi\Controllers\LaporanAspirasiController::class, 'store'])->name('laporan-aspirasi.store');
    Route::get('laporan-aspirasi/track/{ticket_number}', [\App\Modules\LaporanAspirasi\Controllers\LaporanAspirasiController::class, 'track'])->name('laporan-aspirasi.track');

    // Protected endpoints — memerlukan autentikasi dan RBAC
    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        Route::get('laporan-aspirasi', [\App\Modules\LaporanAspirasi\Controllers\LaporanAspirasiController::class, 'index'])->name('laporan-aspirasi.index');
        Route::patch('laporan-aspirasi/{id}/status', [\App\Modules\LaporanAspirasi\Controllers\LaporanAspirasiController::class, 'updateStatus'])->name('laporan-aspirasi.status.update');
        Route::delete('laporan-aspirasi/{id}', [\App\Modules\LaporanAspirasi\Controllers\LaporanAspirasiController::class, 'destroy'])->name('laporan-aspirasi.destroy');
    });

    // =========================================================================
    // Modul: Dashboard (API_SPECIFICATION.md §3.8)
    // =========================================================================
    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        Route::get('dashboard/summary', [\App\Modules\Dashboard\Controllers\DashboardController::class, 'summary'])->name('dashboard.summary');
    });
});
