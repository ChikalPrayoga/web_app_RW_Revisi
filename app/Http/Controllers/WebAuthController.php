<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WebAuthController extends Controller
{
    /**
     * Tampilkan halaman login web.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Proses autentikasi sesi web (session-based authentication).
     * Menggunakan cookie session httpOnly aman, tanpa penyimpanan token di browser storage.
     *
     * @see SYSTEM_ARCHITECTURE.md §4.1, §4.2
     */
    public function login(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $credentials = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ], [
                'email.required' => 'Kolom email wajib diisi.',
                'email.email' => 'Format email tidak valid.',
                'password.required' => 'Kolom kata sandi wajib diisi.',
            ]);
        } catch (ValidationException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data yang dimasukkan tidak valid.',
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // Cek status akun pengguna
            if ($user->status !== 'ACTIVE') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $errorMessage = 'Akun Anda telah dinonaktifkan. Hubungi administrator untuk bantuan.';

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => ['email' => [$errorMessage]],
                    ], 422);
                }

                return back()->withErrors(['email' => $errorMessage])->onlyInput('email');
            }

            // Regenerasi session ID untuk mencegah session fixation (SYSTEM_ARCHITECTURE.md §4.2)
            $request->session()->regenerate();

            // Update timestamp last_login_at
            $user->timestamps = false;
            $user->last_login_at = now();
            $user->save();
            $user->timestamps = true;

            // Catat audit log login web session
            AuditLog::create([
                'user_id' => $user->id,
                'module' => 'Auth',
                'action' => 'LOGIN',
                'entity_type' => 'User',
                'entity_id' => (string) $user->id,
                'old_values' => null,
                'new_values' => ['email' => $user->email, 'channel' => 'web_session'],
                'ip_address' => $request->ip() ?? '0.0.0.0',
                'created_at' => now(),
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'redirect' => route('dashboard'),
                ]);
            }

            return redirect()->intended(route('dashboard'));
        }

        $errorMessage = 'Email atau kata sandi yang Anda masukkan salah.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'errors' => ['email' => [$errorMessage]],
            ], 422);
        }

        return back()->withErrors(['email' => $errorMessage])->onlyInput('email');
    }

    /**
     * Logout dari sesi web: membatalkan sesi httpOnly dan meregenerasi CSRF token.
     *
     * @see SYSTEM_ARCHITECTURE.md §4.2
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            AuditLog::create([
                'user_id' => $user->id,
                'module' => 'Auth',
                'action' => 'LOGOUT',
                'entity_type' => 'User',
                'entity_id' => (string) $user->id,
                'old_values' => null,
                'new_values' => ['channel' => 'web_session'],
                'ip_address' => $request->ip() ?? '0.0.0.0',
                'created_at' => now(),
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
