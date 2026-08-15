<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware `active` — memastikan user yang terautentikasi berstatus ACTIVE.
 * Mencegah user yang dinonaktifkan (INACTIVE) tetap bisa menggunakan token
 * lama yang belum kedaluwarsa.
 *
 * Wajib ditempatkan SETELAH middleware `auth:sanctum` agar `$request->user()`
 * sudah terisi ketika middleware ini berjalan.
 *
 * @see DATABASE_SCHEMA.md §3.1 (kolom status pada tabel users)
 * @see SYSTEM_ARCHITECTURE.md §4.2
 */
class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->status !== 'ACTIVE') {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda telah dinonaktifkan. Hubungi administrator untuk bantuan.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
