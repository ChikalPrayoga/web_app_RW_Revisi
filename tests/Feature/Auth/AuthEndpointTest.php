<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Feature Test untuk endpoint Autentikasi:
 * - POST /api/v1/auth/login
 * - POST /api/v1/auth/logout
 * - GET  /api/v1/auth/me
 *
 * Menerjemahkan Acceptance Criteria dari USER_STORIES.md US-AUTH-01, 02, 03.
 *
 * @see API_SPECIFICATION.md §3.1
 * @see USER_STORIES.md US-AUTH-01, US-AUTH-02, US-AUTH-03
 */
class AuthEndpointTest extends TestCase
{
    use RefreshDatabase;

    private Role $roleKetua;

    private Role $roleWarga;

    protected function setUp(): void
    {
        parent::setUp();

        // Bersihkan rate limiter agar tidak saling menginterferensi antar test
        RateLimiter::clear('login');

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->roleKetua = Role::where('name', 'KETUA_RT')->firstOrFail();
        $this->roleWarga = Role::where('name', 'WARGA')->firstOrFail();
    }

    /**
     * Helper: buat user ACTIVE dengan password yang diketahui.
     */
    private function createActiveUser(string $roleName = 'KETUA_RT', string $rtCode = '001'): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();

        return User::factory()->create([
            'role_id' => $role->id,
            'email' => 'test_'.$roleName.'@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => $roleName === 'KETUA_RT' ? $rtCode : null,
            'status' => 'ACTIVE',
        ]);
    }

    // =========================================================================
    // POST /api/v1/auth/login
    // =========================================================================

    /**
     * [US-AUTH-01] Login sukses dengan kredensial valid.
     * Given: user ACTIVE dengan email & password benar
     * When: POST /api/v1/auth/login
     * Then: 200 OK + token + data user
     */
    public function test_login_sukses_dengan_kredensial_valid(): void
    {
        $user = $this->createActiveUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_at',
                    'user' => [
                        'id',
                        'username',
                        'full_name',
                        'email',
                        'role',
                        'rt_code',
                        'status',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'token_type' => 'Bearer',
                    'user' => [
                        'email' => $user->email,
                        'status' => 'ACTIVE',
                    ],
                ],
            ]);

        // Token harus ada dalam database
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);
    }

    /**
     * Login dengan email yang tidak terdaftar.
     * Given: email tidak ada di database
     * When: POST /api/v1/auth/login
     * Then: 422 dengan pesan error
     */
    public function test_login_gagal_email_tidak_terdaftar(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'tidakada@rw047.id',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['email'],
            ]);
    }

    /**
     * Login dengan password yang salah.
     * Given: email terdaftar tapi password tidak cocok
     * When: POST /api/v1/auth/login
     * Then: 422 dengan pesan error generik (tidak membocorkan apakah email yang salah)
     */
    public function test_login_gagal_password_salah(): void
    {
        $user = $this->createActiveUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'PasswordSalah999!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['email'],
            ]);
    }

    /**
     * Login dengan format email yang tidak valid.
     * Given: email bukan format valid
     * When: POST /api/v1/auth/login
     * Then: 422 validasi email
     */
    public function test_login_gagal_format_email_tidak_valid(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'bukan-email-valid',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Login dengan password yang terlalu pendek (< 8 karakter).
     * Given: password < 8 karakter
     * When: POST /api/v1/auth/login
     * Then: 422 validasi password
     */
    public function test_login_gagal_password_terlalu_pendek(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@rw047.id',
            'password' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Login dengan field yang kosong.
     * Given: body kosong
     * When: POST /api/v1/auth/login
     * Then: 422 validasi email dan password
     */
    public function test_login_gagal_field_kosong(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Login dengan akun yang INACTIVE.
     * Given: user terdaftar tapi berstatus INACTIVE
     * When: POST /api/v1/auth/login
     * Then: 422 dengan pesan akun nonaktif
     */
    public function test_login_gagal_akun_inactive(): void
    {
        $role = Role::where('name', 'WARGA')->firstOrFail();

        $inactiveUser = User::factory()->create([
            'role_id' => $role->id,
            'email' => 'nonaktif@rw047.id',
            'password' => Hash::make('Password123!'),
            'status' => 'INACTIVE',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $inactiveUser->email,
            'password' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * [NFR-01] Rate limiting login: lebih dari 5 percobaan dalam 1 menit.
     * Given: 5 percobaan login gagal berturut-turut dengan IP+email yang sama
     * When: percobaan ke-6
     * Then: 429 Too Many Requests
     *
     * @see API_SPECIFICATION.md §2.5
     * @see PRD NFR-01
     */
    public function test_login_rate_limit_429_setelah_lima_percobaan(): void
    {
        // Gunakan user yang ada (email nyata) agar rate limiter terpanggil dengan benar
        $user = $this->createActiveUser('WARGA');

        $payload = [
            'email' => $user->email,
            'password' => 'PasswordSalah999!',
        ];

        // 5 percobaan pertama harus merespons 422 (gagal kredensial)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', $payload)->assertStatus(422);
        }

        // Percobaan ke-6 harus diblokir oleh rate limiter
        $this->postJson('/api/v1/auth/login', $payload)
            ->assertStatus(429)
            ->assertJson([
                'success' => false,
            ]);
    }

    // =========================================================================
    // POST /api/v1/auth/logout
    // =========================================================================

    /**
     * [US-AUTH-02] Logout sukses dengan token valid.
     * Given: user terautentikasi
     * When: POST /api/v1/auth/logout
     * Then: 200 OK, token dicabut
     */
    public function test_logout_sukses_dengan_token_valid(): void
    {
        $user = $this->createActiveUser();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Berhasil keluar dari sistem.',
                'data' => null,
            ]);

        // Token harus sudah dihapus dari database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'test_token',
        ]);
    }

    /**
     * Logout tanpa token (tidak terautentikasi).
     * Given: tidak ada Authorization header
     * When: POST /api/v1/auth/logout
     * Then: 401 Unauthorized
     */
    public function test_logout_gagal_tanpa_token(): void
    {
        $this->postJson('/api/v1/auth/logout')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Logout dengan token yang sudah digunakan sebelumnya (token dicabut).
     * Given: token sudah logout sekali
     * When: gunakan token yang sama lagi
     * Then: 401 Unauthorized
     */
    public function test_logout_gagal_dengan_token_yang_sudah_dicabut(): void
    {
        $user = $this->createActiveUser();
        $token = $user->createToken('test_token')->plainTextToken;

        // Logout pertama — berhasil
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertStatus(200);

        // Reset in-memory guard cache untuk mensimulasikan request baru dari client
        app('auth')->forgetGuards();

        // Logout kedua dengan token yang sama — harus 401 karena token sudah dihapus di DB
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertStatus(401);
    }

    /**
     * Logout dengan akun yang INACTIVE.
     * Given: user berhasil login sebelumnya, kemudian statusnya diubah INACTIVE
     * When: POST /api/v1/auth/logout dengan token lama
     * Then: 403 Forbidden (middleware active menolak)
     */
    public function test_logout_gagal_akun_dinonaktifkan_setelah_login(): void
    {
        $user = $this->createActiveUser();
        $token = $user->createToken('test_token')->plainTextToken;

        // Nonaktifkan akun setelah login
        $user->update(['status' => 'INACTIVE']);

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    // =========================================================================
    // GET /api/v1/auth/me
    // =========================================================================

    /**
     * [US-AUTH-03] GET /me sukses — data profil lengkap dikembalikan.
     * Given: user ACTIVE terautentikasi
     * When: GET /api/v1/auth/me
     * Then: 200 OK + data profil sesuai spesifikasi
     */
    public function test_me_sukses_mengembalikan_profil_user(): void
    {
        $user = $this->createActiveUser('KETUA_RT', '005');
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'username',
                    'full_name',
                    'email',
                    'phone_number',
                    'role',
                    'rt_code',
                    'status',
                    'last_login_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'email' => $user->email,
                    'role' => 'KETUA_RT',
                    'rt_code' => '005',
                    'status' => 'ACTIVE',
                ],
            ]);
    }

    /**
     * GET /me tanpa token.
     * Given: tidak ada Authorization header
     * When: GET /api/v1/auth/me
     * Then: 401 Unauthorized
     */
    public function test_me_gagal_tanpa_token(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * GET /me dengan user yang statusnya INACTIVE setelah login.
     * Given: user berhasil login sebelumnya, kemudian di-INACTIVE-kan
     * When: GET /api/v1/auth/me dengan token lama
     * Then: 403 Forbidden
     */
    public function test_me_gagal_akun_dinonaktifkan_setelah_login(): void
    {
        $user = $this->createActiveUser();
        $token = $user->createToken('test_token')->plainTextToken;

        // Nonaktifkan akun
        $user->update(['status' => 'INACTIVE']);

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * GET /me memastikan response tidak mengandung field sensitif (password, remember_token).
     * Given: user terautentikasi
     * When: GET /api/v1/auth/me
     * Then: response TIDAK mengandung kolom password atau remember_token
     */
    public function test_me_tidak_mengembalikan_field_sensitif(): void
    {
        $user = $this->createActiveUser();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/auth/me');
        $responseData = $response->json('data');

        $this->assertArrayNotHasKey('password', $responseData);
        $this->assertArrayNotHasKey('remember_token', $responseData);
        $this->assertArrayNotHasKey('tokens', $responseData);
    }

    /**
     * Login berhasil mencatat audit log dengan action LOGIN.
     * Given: login sukses
     * When: POST /api/v1/auth/login
     * Then: tabel audit_logs memiliki entri dengan action=LOGIN
     */
    public function test_login_mencatat_audit_log(): void
    {
        $user = $this->createActiveUser();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'module' => 'Auth',
            'action' => 'LOGIN',
            'entity_type' => 'User',
        ]);
    }

    /**
     * Logout berhasil mencatat audit log dengan action LOGOUT.
     * Given: logout sukses
     * When: POST /api/v1/auth/logout
     * Then: tabel audit_logs memiliki entri dengan action=LOGOUT
     */
    public function test_logout_mencatat_audit_log(): void
    {
        $user = $this->createActiveUser();
        $token = $user->createToken('test_token')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'module' => 'Auth',
            'action' => 'LOGOUT',
            'entity_type' => 'User',
        ]);
    }
}
