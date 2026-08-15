<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Modules\Auth\Exceptions\AccountInactiveException;
use App\Modules\Auth\Exceptions\InvalidCredentialsException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;

/**
 * Service untuk seluruh business logic Autentikasi.
 * Mengikuti pola Layered Architecture: Controller memanggil Service,
 * Service berinteraksi dengan Model/Repository.
 *
 * @see SYSTEM_ARCHITECTURE.md §1.2, §4.1, §4.2
 * @see API_SPECIFICATION.md §3.1
 */
class AuthService
{
    /**
     * Memvalidasi kredensial pengguna dan menerbitkan Sanctum access token.
     * Update `last_login_at` dan catat audit log setiap kali login berhasil.
     *
     * Token kedaluwarsa setelah 8 jam (480 menit) agar sesi tidak terlalu lama
     * terbuka untuk aplikasi administrasi tingkat RW (lihat PRD NFR-01).
     *
     * @param  array{email: string, password: string}  $credentials
     *
     * @throws InvalidCredentialsException jika email tidak ditemukan atau password salah
     * @throws AccountInactiveException jika akun ditemukan tapi berstatus INACTIVE
     *
     * @see US-AUTH-01
     */
    public function login(array $credentials, string $ipAddress): array
    {
        /** @var User|null $user */
        $user = User::with('role')->where('email', $credentials['email'])->first();

        // Email tidak terdaftar atau password salah — pesan error dibuat generik
        // agar tidak memberikan informasi apakah email terdaftar (enumeration attack prevention)
        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            throw new InvalidCredentialsException;
        }

        // Akun ditemukan tapi nonaktif — diblokir sebelum menerbitkan token
        if ($user->status !== 'ACTIVE') {
            throw new AccountInactiveException;
        }

        // Hapus token lama (opsional — one-session policy, cegah penumpukan token)
        $user->tokens()->delete();

        /** @var NewAccessToken $tokenResult */
        $tokenResult = $user->createToken(
            name: 'auth_token',
            expiresAt: now()->addMinutes(480),
        );

        // Update last_login_at tanpa memicu event Observer lain
        $user->timestamps = false;
        $user->last_login_at = now();
        $user->save();
        $user->timestamps = true;

        // Catat audit log login
        AuditLog::create([
            'user_id' => $user->id,
            'module' => 'Auth',
            'action' => 'LOGIN',
            'entity_type' => 'User',
            'entity_id' => (string) $user->id,
            'old_values' => null,
            'new_values' => ['email' => $user->email],
            'ip_address' => $ipAddress,
            'created_at' => now(),
        ]);

        return [
            'access_token' => $tokenResult->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $tokenResult->accessToken->expires_at?->toIso8601String(),
            'user' => $user,
        ];
    }

    /**
     * Mencabut (invalidasi) token aktif pengguna yang sedang login.
     * Catat audit log logout untuk keperluan audit trail.
     *
     * @see SYSTEM_ARCHITECTURE.md §4.2 (Session invalidation)
     * @see US-AUTH-02
     */
    public function logout(User $user, string $ipAddress): void
    {
        // Hapus hanya token yang digunakan pada request ini (bukan semua token)
        $user->currentAccessToken()->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'module' => 'Auth',
            'action' => 'LOGOUT',
            'entity_type' => 'User',
            'entity_id' => (string) $user->id,
            'old_values' => null,
            'new_values' => null,
            'ip_address' => $ipAddress,
            'created_at' => now(),
        ]);
    }

    /**
     * Mengambil data profil user yang sedang terautentikasi.
     * Load relasi role agar field `role` dapat diisi di API Resource.
     *
     * @see API_SPECIFICATION.md §3.1.3
     * @see US-AUTH-03
     */
    public function me(User $user): User
    {
        return $user->load('role');
    }
}
