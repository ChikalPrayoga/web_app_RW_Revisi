<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware `permission` — memeriksa apakah user terautentikasi
 * memiliki permission tertentu sebelum mengizinkan akses ke endpoint.
 *
 * Digunakan sebagai middleware alias: `permission:nama.permission`.
 * Merespons 403 Forbidden jika user tidak punya permission yang diminta,
 * sesuai SYSTEM_ARCHITECTURE.md §4.3.
 *
 * Contoh penggunaan di route: ->middleware('permission:surat.verify.rt')
 */
class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk mengakses sumber daya ini.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
