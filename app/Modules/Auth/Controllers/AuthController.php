<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Exceptions\AccountInactiveException;
use App\Modules\Auth\Exceptions\InvalidCredentialsException;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Resources\AuthUserResource;
use App\Modules\Auth\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller tipis — hanya menangani HTTP lifecycle:
 * terima request → delegasikan ke Service → kembalikan response.
 * Tidak ada business logic di sini (sesuai RULES.md §1.2 & AGENTS.md §5.3).
 *
 * @see API_SPECIFICATION.md §3.1
 */
class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    /**
     * POST /api/v1/auth/login
     * Autentikasi pengguna dan menerbitkan access token.
     * Rate limited: 5 percobaan/menit per kombinasi IP+email (lihat RouteServiceProvider).
     *
     * @see API_SPECIFICATION.md §3.1.1
     * @see US-AUTH-01
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                credentials: $request->only('email', 'password'),
                ipAddress: $request->ip() ?? '0.0.0.0',
            );

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil.',
                'data' => [
                    'access_token' => $result['access_token'],
                    'token_type' => $result['token_type'],
                    'expires_at' => $result['expires_at'],
                    'user' => new AuthUserResource($result['user']),
                ],
            ]);
        } catch (InvalidCredentialsException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [
                    'email' => ['Kredensial tidak cocok dengan data kami.'],
                ],
            ], 422);
        } catch (AccountInactiveException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [
                    'email' => [$e->getMessage()],
                ],
            ], 422);
        }
    }

    /**
     * POST /api/v1/auth/logout
     * Mencabut token aktif — invalidasi sesi pengguna.
     * Dilindungi middleware auth:sanctum + active.
     *
     * @see API_SPECIFICATION.md §3.1.2
     * @see US-AUTH-02
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout(
            user: $request->user(),
            ipAddress: $request->ip() ?? '0.0.0.0',
        );

        return response()->json([
            'success' => true,
            'message' => 'Berhasil keluar dari sistem.',
            'data' => null,
        ]);
    }

    /**
     * GET /api/v1/auth/me
     * Mengambil data profil pengguna yang sedang terautentikasi.
     * Dilindungi middleware auth:sanctum + active.
     *
     * @see API_SPECIFICATION.md §3.1.3
     * @see US-AUTH-03
     */
    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->me($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Data profil berhasil diambil.',
            'data' => new AuthUserResource($user),
        ]);
    }
}
