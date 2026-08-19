<?php

declare(strict_types=1);

use App\Http\Controllers\PortalWargaController;
use App\Http\Controllers\WebAuthController;
use App\Modules\InformasiPublik\Controllers\InformasiPublikWebController;
use App\Modules\Kependudukan\Controllers\KependudukanWebController;
use App\Modules\Keuangan\Controllers\KeuanganWebController;
use App\Modules\Persuratan\Controllers\PersuratanWebController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — SIM Layanan Warga RW 047
|--------------------------------------------------------------------------
|
| Rute web untuk antarmuka Blade pengguna dan pengurus RW 047.
| Seluruh autentikasi web menggunakan session/cookie httpOnly aman.
|
*/

// =========================================================================
// Portal Warga & Informasi Publik — Public Routes (Tanpa Login)
// =========================================================================
Route::get('/', [PortalWargaController::class, 'index'])->name('portal.home');
Route::get('/informasi', [PortalWargaController::class, 'informasiIndex'])->name('portal.informasi.index');
Route::get('/informasi/{id}', [PortalWargaController::class, 'informasiDetail'])->name('portal.informasi.show');

// Autentikasi Web (Guest only) dengan rate limiter login (5x/menit per NFR-01)
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [WebAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login.post');
});

// =========================================================================
// Modul: Persuratan — Public Routes (Tanpa Login, Tidak Menggunakan 'guest')
// Form pengajuan dan tracking dapat diakses siapa pun.
// =========================================================================
Route::prefix('surat')->name('persuratan.public.')->group(function (): void {
    Route::get('ajukan', [PersuratanWebController::class, 'createForm'])->name('create');
    Route::post('ajukan', [PersuratanWebController::class, 'store'])->name('store');
    Route::get('sukses/{tracking_code}', [PersuratanWebController::class, 'success'])->name('success');
    Route::get('lacak', [PersuratanWebController::class, 'trackForm'])->name('track');
    Route::get('lacak/{tracking_code}', [PersuratanWebController::class, 'trackResult'])->name('track_result');
});

// =========================================================================
// Modul: Laporan & Aspirasi — Public Routes (Tanpa Login)
// =========================================================================
Route::prefix('laporan-aspirasi')->name('portal.laporan.')->group(function (): void {
    Route::get('ajukan', [PortalWargaController::class, 'laporanCreate'])->name('create');
    Route::post('ajukan', [PortalWargaController::class, 'laporanStore'])->name('store');
    Route::get('sukses/{ticket_number}', [PortalWargaController::class, 'laporanSuccess'])->name('success');
    Route::get('lacak', [PortalWargaController::class, 'laporanTrack'])->name('track');
    Route::get('lacak/{ticket_number}', [PortalWargaController::class, 'laporanTrackResult'])->name('track_result');
});

// Halaman yang dilindungi autentikasi sesi web & status akun aktif
Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/dashboard', [\App\Modules\Dashboard\Controllers\DashboardWebController::class, 'index'])->name('dashboard');

    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

    // =========================================================================
    // Modul: Kependudukan (Blade Web Views & Actions)
    // =========================================================================
    Route::prefix('kependudukan')->name('kependudukan.')->group(function (): void {
        // Kartu Keluarga
        Route::get('kartu-keluarga', [KependudukanWebController::class, 'indexKK'])->name('kk.index');
        Route::get('kartu-keluarga/tambah', [KependudukanWebController::class, 'createKK'])->name('kk.create');
        Route::post('kartu-keluarga', [KependudukanWebController::class, 'storeKK'])->name('kk.store');

        // Data Warga
        Route::get('warga', [KependudukanWebController::class, 'indexWarga'])->name('warga.index');
        Route::get('warga/tambah', [KependudukanWebController::class, 'createWarga'])->name('warga.create');
        Route::post('warga', [KependudukanWebController::class, 'storeWarga'])->name('warga.store');
        Route::get('warga/{nik_hash}', [KependudukanWebController::class, 'showWarga'])->name('warga.show');
        Route::get('warga/{nik_hash}/edit', [KependudukanWebController::class, 'editWarga'])->name('warga.edit');
        Route::patch('warga/{nik_hash}', [KependudukanWebController::class, 'updateWarga'])->name('warga.update');
        Route::get('warga/{nik_hash}/verifikasi', [KependudukanWebController::class, 'verifyWargaForm'])->name('warga.verify.form');
        Route::post('warga/{nik_hash}/verifikasi', [KependudukanWebController::class, 'verifyWarga'])->name('warga.verify');
    });

    // =========================================================================
    // Modul: Persuratan — Protected Routes (Pengurus)
    // =========================================================================
    Route::prefix('surat')->name('persuratan.')->group(function (): void {
        Route::get('/', [PersuratanWebController::class, 'index'])->name('index');
        Route::get('{id}', [PersuratanWebController::class, 'show'])->name('show');
        Route::get('{id}/verifikasi', [PersuratanWebController::class, 'verifyForm'])->name('verify.form');
        Route::post('{id}/verifikasi', [PersuratanWebController::class, 'verify'])->name('verify');
    });

    // =========================================================================
    // Modul: Keuangan (Blade Web Views & Actions)
    // =========================================================================
    Route::prefix('keuangan')->name('keuangan.')->group(function (): void {
        // Iuran Warga
        Route::get('iuran', [KeuanganWebController::class, 'indexIuran'])->name('iuran.index');
        Route::get('iuran/create', [KeuanganWebController::class, 'createIuran'])->name('iuran.create');
        Route::post('iuran', [KeuanganWebController::class, 'storeIuran'])->name('iuran.store');
        Route::get('iuran/approval', [KeuanganWebController::class, 'approvalIuran'])->name('iuran.approval');
        Route::post('iuran/{id}/approve', [KeuanganWebController::class, 'processApprovalIuran'])->name('iuran.approve');

        // Kas Keluar RW
        Route::get('kas-keluar', [KeuanganWebController::class, 'indexKasKeluar'])->name('kas-keluar.index');
        Route::get('kas-keluar/create', [KeuanganWebController::class, 'createKasKeluar'])->name('kas-keluar.create');
        Route::post('kas-keluar', [KeuanganWebController::class, 'storeKasKeluar'])->name('kas-keluar.store');
        Route::get('kas-keluar/approval', [KeuanganWebController::class, 'approvalKasKeluar'])->name('kas-keluar.approval');
        Route::post('kas-keluar/{id}/approve', [KeuanganWebController::class, 'processApprovalKasKeluar'])->name('kas-keluar.approve');

        // Rekapitulasi Keuangan
        Route::get('rekap', [KeuanganWebController::class, 'rekap'])->name('rekap.index');
    });

    // =========================================================================
    // Modul: Informasi Publik — Protected Routes (Pengurus)
    // =========================================================================
    Route::prefix('informasi-publik')->name('informasi-publik.')->group(function (): void {
        Route::get('/', [InformasiPublikWebController::class, 'index'])->name('index');
        Route::get('tambah', [InformasiPublikWebController::class, 'create'])->name('create');
        Route::post('/', [InformasiPublikWebController::class, 'store'])->name('store');
        Route::get('{id}/edit', [InformasiPublikWebController::class, 'edit'])->name('edit');
        Route::match(['put', 'patch'], '{id}', [InformasiPublikWebController::class, 'update'])->name('update');
        Route::delete('{id}', [InformasiPublikWebController::class, 'destroy'])->name('destroy');
    });

    // =========================================================================
    // Modul: Laporan & Aspirasi — Protected Routes (Pengurus)
    // =========================================================================
    Route::prefix('laporan-aspirasi')->name('laporan-aspirasi.')->group(function (): void {
        Route::get('/', [\App\Modules\LaporanAspirasi\Controllers\LaporanAspirasiWebController::class, 'index'])->name('index');
        Route::get('{id}', [\App\Modules\LaporanAspirasi\Controllers\LaporanAspirasiWebController::class, 'show'])->name('show');
        Route::post('{id}/status', [\App\Modules\LaporanAspirasi\Controllers\LaporanAspirasiWebController::class, 'updateStatus'])->name('status.update');
    });
});
