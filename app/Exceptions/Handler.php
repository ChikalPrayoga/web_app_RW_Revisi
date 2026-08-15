<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e): void {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     * Seluruh response API mengikuti format envelope {success, message, errors?}
     * sesuai API_SPECIFICATION.md §1.6.
     *
     * Pesan error TIDAK membocorkan detail internal (stack trace, query SQL, path server)
     * sesuai RULES.md §3.1 dan UI_UX_SPECIFICATION.md §3.3.
     */
    public function render($request, Throwable $e): mixed
    {
        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        }

        // Hanya terapkan format JSON untuk request yang mengharapkan JSON (API request)
        if ($request->expectsJson()) {
            return $this->renderApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Format exception menjadi response JSON yang sesuai envelope API.
     */
    private function renderApiException(Request $request, Throwable $e): JsonResponse
    {
        // 401 Unauthenticated — token tidak ada/tidak valid
        if ($e instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan login terlebih dahulu.',
            ], 401);
        }

        // 422 Validation Error — field wajib kosong/format salah
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Data yang dikirimkan tidak valid.',
                'errors' => $e->errors(),
            ], 422);
        }

        // HTTP exception (403, 404, 429, dll.)
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();

            $messages = [
                403 => 'Anda tidak memiliki izin untuk mengakses sumber daya ini.',
                404 => 'Sumber daya yang diminta tidak ditemukan.',
                405 => 'Metode HTTP tidak diizinkan untuk endpoint ini.',
                429 => 'Terlalu banyak permintaan. Silakan coba lagi dalam beberapa saat.',
            ];

            return response()->json([
                'success' => false,
                'message' => $messages[$statusCode] ?? ($e->getMessage() ?: 'Terjadi kesalahan pada server.'),
            ], $statusCode);
        }

        // 500 Internal Server Error — jangan bocorkan detail ke user
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan pada server. Silakan coba lagi.',
        ], 500);
    }
}
