<?php

declare(strict_types=1);

use App\Http\Controllers\WebAuthController;
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

// Root redirect
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Autentikasi Web (Guest only) dengan rate limiter login (5x/menit per NFR-01)
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [WebAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login.post');
});

// Halaman yang dilindungi autentikasi sesi web
Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');
});
