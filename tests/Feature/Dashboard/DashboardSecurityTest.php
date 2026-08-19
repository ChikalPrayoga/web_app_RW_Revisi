<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Enums\JenisSurat;
use App\Enums\RoleName;
use App\Enums\StatusCatatanIuran;
use App\Enums\StatusPengajuanSurat;
use App\Models\CatatanIuran;
use App\Models\IuranType;
use App\Models\KartuKeluarga;
use App\Models\PengajuanSurat;
use App\Models\Role;
use App\Models\User;
use App\Models\Warga;
use Database\Seeders\IuranTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Security & Hardening Tests untuk Dashboard.
 *
 * Menguji: Area scoping isolation, Anti-IDOR / Query parameter tampering, PII Protection, Inactive user block,
 * Data privacy isolation untuk WARGA pada Web, dan Division-by-zero protection.
 */
class DashboardSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $ketuaRt01;

    private User $ketuaRt02;

    private User $inactivePengurus;

    private User $wargaUser;

    private KartuKeluarga $kkRt02;

    private Warga $wargaRt02;

    private string $rawNoKk02 = '3216010101230002';

    private string $rawNik02 = '3216011505900002';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(IuranTypeSeeder::class);

        $roleRt = Role::where('name', RoleName::KETUA_RT->value)->firstOrFail();
        $roleWarga = Role::where('name', RoleName::WARGA->value)->firstOrFail();

        $this->ketuaRt01 = User::factory()->create([
            'role_id' => $roleRt->id,
            'rt_code' => '001',
            'status' => 'ACTIVE',
        ]);

        $this->ketuaRt02 = User::factory()->create([
            'role_id' => $roleRt->id,
            'rt_code' => '002',
            'status' => 'ACTIVE',
        ]);

        $this->inactivePengurus = User::factory()->create([
            'role_id' => $roleRt->id,
            'rt_code' => '001',
            'status' => 'INACTIVE',
        ]);

        $this->wargaUser = User::factory()->create([
            'role_id' => $roleWarga->id,
            'status' => 'ACTIVE',
        ]);

        // Data RT 002 Saja
        $this->kkRt02 = KartuKeluarga::create([
            'no_kk' => $this->rawNoKk02,
            'rt_code' => '002',
            'alamat_lengkap' => 'Jl. Anggrek No. 22 RT 002',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ]);

        $this->wargaRt02 = Warga::create([
            'kartu_keluarga_id' => $this->kkRt02->id,
            'nik' => $this->rawNik02,
            'no_kk' => $this->rawNoKk02,
            'nama_lengkap' => 'Warga RT 002',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '1992-02-02',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
            'status_warga' => 'TETAP',
            'verification_status' => 'TERVERIFIKASI',
        ]);

        PengajuanSurat::create([
            'tracking_code' => 'SRT-20260818-RT0202',
            'warga_id' => $this->wargaRt02->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan RT 002',
            'current_status' => StatusPengajuanSurat::SUBMITTED->value,
            'tanggal_pengajuan' => now(),
        ]);

        $iuranType = IuranType::firstOrFail();
        CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt02->id,
            'iuran_type_id' => $iuranType->id,
            'periode_bulan' => (int) now()->format('n'),
            'periode_tahun' => (int) now()->format('Y'),
            'nominal' => 100000.00,
            'status' => StatusCatatanIuran::APPROVED->value,
            'recorded_by_user_id' => $this->ketuaRt02->id,
        ]);
    }

    public function test_ketua_rt_cannot_see_metrics_from_other_rt(): void
    {
        Sanctum::actingAs($this->ketuaRt01);

        $response = $this->getJson('/api/v1/dashboard/summary');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_warga' => 0, // RT 001 tidak memiliki warga
                    'total_kk' => 0,    // RT 001 tidak memiliki KK
                    'surat_menunggu_verifikasi' => 0, // Surat RT 002 tidak dihitung
                    'total_iuran_bulan_ini' => 0.0,   // Iuran RT 002 tidak dihitung
                    'kepatuhan_iuran_persen' => 0.0,
                ],
            ]);
    }

    public function test_ketua_rt_query_tampering_is_prevented(): void
    {
        Sanctum::actingAs($this->ketuaRt01);

        // Mencoba bypass area scoping dengan inject ?rt_code=002 pada query string
        $response = $this->getJson('/api/v1/dashboard/summary?rt_code=002');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_warga' => 0, // Backend mengabaikan query param dan tetap menggunakan $user->rt_code ('001')
                    'total_kk' => 0,
                    'surat_menunggu_verifikasi' => 0,
                    'total_iuran_bulan_ini' => 0.0,
                    'kepatuhan_iuran_persen' => 0.0,
                ],
            ]);
    }

    public function test_dashboard_summary_does_not_leak_unmasked_pii(): void
    {
        Sanctum::actingAs($this->ketuaRt02);

        $response = $this->getJson('/api/v1/dashboard/summary');

        $response->assertStatus(200);

        $jsonContent = (string) $response->getContent();

        // Pastikan tidak ada NIK plaintext ataupun No KK plaintext yang bocor di JSON payload
        $this->assertStringNotContainsString($this->rawNik02, $jsonContent);
        $this->assertStringNotContainsString($this->rawNoKk02, $jsonContent);
    }

    public function test_inactive_user_cannot_access_dashboard_api_or_web(): void
    {
        // 1. API Sanctum
        Sanctum::actingAs($this->inactivePengurus);
        $apiResponse = $this->getJson('/api/v1/dashboard/summary');
        $apiResponse->assertStatus(403);

        // 2. Web Session
        $webResponse = $this->actingAs($this->inactivePengurus)->get('/dashboard');
        $webResponse->assertStatus(403);
    }

    public function test_warga_web_dashboard_does_not_leak_internal_financial_or_pengurus_controls(): void
    {
        $response = $this->actingAs($this->wargaUser)->get('/dashboard');

        $response->assertStatus(200);
        // Memastikan kartu overview keuangan internal pengurus tidak tampil untuk Warga
        $response->assertDontSee('Saldo Kas RW 047');
        $response->assertDontSee('Penerimaan Iuran RW (Bulan Ini)');
        $response->assertDontSee('Persetujuan Keuangan');
        // Memastikan Action Center tidak menampilkan antrean pengurus internal
        $response->assertSee('Semua Tugas Selesai!');
    }

    public function test_division_by_zero_safety_when_total_kk_is_zero(): void
    {
        // Login sebagai Ketua RT 001 yang memiliki 0 KK
        Sanctum::actingAs($this->ketuaRt01);

        $response = $this->getJson('/api/v1/dashboard/summary');

        $response->assertStatus(200);
        $this->assertEquals(0.0, $response->json('data.kepatuhan_iuran_persen'));
    }
}
