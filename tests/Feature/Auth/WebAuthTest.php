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

class WebAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('login');
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    /**
     * Halaman login web dapat diakses.
     */
    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('SIM Layanan Warga');
        $response->assertSee('Masuk ke Sistem');
    }

    /**
     * Dashboard hanya dapat diakses oleh user terautentikasi.
     */
    public function test_dashboard_redirects_unauthenticated_users(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    /**
     * Dashboard merender komponen sesuai role user yang login.
     */
    public function test_dashboard_renders_for_authenticated_user(): void
    {
        $role = Role::where('name', 'KETUA_RT')->firstOrFail();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'full_name' => 'Pak Budi RT 001',
            'rt_code' => '001',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Pak Budi RT 001');
        $response->assertSee('Ketua RT');
        $response->assertSee('RT 001');
        $response->assertSee('Data Kependudukan');
        $response->assertDontSee('Kelola Pengguna'); // Super Admin only
    }

    /**
     * Role Super Admin melihat menu Administrasi / Kelola Pengguna.
     */
    public function test_super_admin_sees_user_management_menu(): void
    {
        $role = Role::where('name', 'SUPER_ADMIN')->firstOrFail();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'full_name' => 'Admin Utama',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Kelola Pengguna');
        $response->assertSee('Log Aktivitas');
    }

    /**
     * Web session login berhasil via standard POST dengan kredensial yang sah.
     */
    public function test_web_session_login_successful(): void
    {
        $role = Role::where('name', 'WARGA')->firstOrFail();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'email' => 'warga@rw047.id',
            'password' => Hash::make('Password123!'),
            'status' => 'ACTIVE',
        ]);

        $response = $this->post('/login', [
            'email' => 'warga@rw047.id',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Web login via AJAX/JSON mengembalikan respons JSON dan mengotentikasi sesi httpOnly.
     */
    public function test_web_ajax_login_successful_returns_json_and_sets_session(): void
    {
        $role = Role::where('name', 'WARGA')->firstOrFail();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'email' => 'warga_ajax@rw047.id',
            'password' => Hash::make('Password123!'),
            'status' => 'ACTIVE',
        ]);

        $response = $this->postJson('/login', [
            'email' => 'warga_ajax@rw047.id',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login berhasil.',
                'redirect' => route('dashboard'),
            ]);

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Web login gagal dengan kredensial yang tidak cocok.
     */
    public function test_web_login_failed_with_invalid_credentials(): void
    {
        $role = Role::where('name', 'WARGA')->firstOrFail();
        User::factory()->create([
            'role_id' => $role->id,
            'email' => 'warga_salah@rw047.id',
            'password' => Hash::make('Password123!', ['rounds' => 4]),
            'status' => 'ACTIVE',
        ]);

        $response = $this->postJson('/login', [
            'email' => 'warga_salah@rw047.id',
            'password' => 'PasswordSalah!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertGuest();
    }

    /**
     * Web login gagal untuk akun nonaktif (INACTIVE).
     */
    public function test_web_login_failed_for_inactive_account(): void
    {
        $role = Role::where('name', 'WARGA')->firstOrFail();
        User::factory()->create([
            'role_id' => $role->id,
            'email' => 'warga_inactive@rw047.id',
            'password' => Hash::make('Password123!', ['rounds' => 4]),
            'status' => 'INACTIVE',
        ]);

        $response = $this->postJson('/login', [
            'email' => 'warga_inactive@rw047.id',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertGuest();
    }

    /**
     * Rate limiting pada endpoint web login (5x/menit).
     */
    public function test_web_login_rate_limiting_after_5_attempts(): void
    {
        $payload = [
            'email' => 'ratelimit@rw047.id',
            'password' => 'PasswordSalah!',
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/login', $payload)->assertStatus(422);
        }

        // Percobaan ke-6 diblokir rate limiter (429)
        $this->postJson('/login', $payload)->assertStatus(429);
    }

    /**
     * Web logout berhasil menghapus sesi dan mengarahkan ke login.
     */
    public function test_web_logout_successful(): void
    {
        $role = Role::where('name', 'WARGA')->firstOrFail();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * User yang sudah logout tidak dapat mengakses dashboard.
     */
    public function test_logged_out_user_cannot_access_dashboard(): void
    {
        $role = Role::where('name', 'WARGA')->firstOrFail();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'status' => 'ACTIVE',
        ]);

        // Login -> logout -> akses dashboard
        $this->actingAs($user)->post('/logout');
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    /**
     * Web login dan logout mencatat entri audit log.
     */
    public function test_web_login_and_logout_creates_audit_logs(): void
    {
        $role = Role::where('name', 'WARGA')->firstOrFail();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'email' => 'audit_user@rw047.id',
            'password' => Hash::make('Password123!'),
            'status' => 'ACTIVE',
        ]);

        $this->post('/login', [
            'email' => 'audit_user@rw047.id',
            'password' => 'Password123!',
        ])->assertRedirect('/dashboard');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'module' => 'Auth',
            'action' => 'LOGIN',
        ]);

        $this->post('/logout')->assertRedirect('/login');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'module' => 'Auth',
            'action' => 'LOGOUT',
        ]);
    }

    /**
     * Cookie sesi memiliki atribut HttpOnly dan SameSite (Lax).
     */
    public function test_session_cookie_attributes_are_httponly_and_samesite(): void
    {
        $role = Role::where('name', 'WARGA')->firstOrFail();
        User::factory()->create([
            'role_id' => $role->id,
            'email' => 'cookie_check@rw047.id',
            'password' => Hash::make('Password123!'),
            'status' => 'ACTIVE',
        ]);

        $response = $this->post('/login', [
            'email' => 'cookie_check@rw047.id',
            'password' => 'Password123!',
        ]);

        $sessionCookieName = config('session.cookie');
        $sessionCookie = null;

        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $sessionCookieName) {
                $sessionCookie = $cookie;
                break;
            }
        }

        $this->assertNotNull($sessionCookie, "Session cookie {$sessionCookieName} must be present in response");
        $this->assertTrue($sessionCookie->isHttpOnly(), 'Session cookie must be HttpOnly to prevent XSS access');
        $this->assertEquals('lax', strtolower((string) $sessionCookie->getSameSite()), 'Session cookie SameSite must be Lax');
    }

    /**
     * Sesi yang didapat saat login dapat digunakan untuk mengakses dashboard.
     */
    public function test_session_allows_access_to_dashboard(): void
    {
        $role = Role::where('name', 'WARGA')->firstOrFail();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'email' => 'session_dash@rw047.id',
            'password' => Hash::make('Password123!'),
            'status' => 'ACTIVE',
        ]);

        $loginResponse = $this->post('/login', [
            'email' => 'session_dash@rw047.id',
            'password' => 'Password123!',
        ]);

        $loginResponse->assertRedirect('/dashboard');

        // Request ke dashboard dengan sesi aktif
        $dashboardResponse = $this->get('/dashboard');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee($user->full_name);
    }
}
