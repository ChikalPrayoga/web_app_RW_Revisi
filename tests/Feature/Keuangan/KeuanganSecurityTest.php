<?php

declare(strict_types=1);

namespace Tests\Feature\Keuangan;

use App\Enums\RoleName;
use App\Enums\StatusCatatanIuran;
use App\Enums\StatusKasKeluar;
use App\Models\AuditLog;
use App\Models\CatatanIuran;
use App\Models\IuranType;
use App\Models\KartuKeluarga;
use App\Models\KasKeluar;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\IuranTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KeuanganSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $ketuaRt01;

    private User $ketuaRt02;

    private User $bendaharaRw;

    private User $ketuaRw;

    private User $wargaUser;

    private KartuKeluarga $kkRt01;

    private KartuKeluarga $kkRt02;

    private IuranType $iuranTypeIkk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(IuranTypeSeeder::class);

        $roleRt = Role::where('name', RoleName::KETUA_RT->value)->firstOrFail();
        $roleBendahara = Role::where('name', RoleName::BENDAHARA_RW->value)->firstOrFail();
        $roleKetuaRw = Role::where('name', RoleName::KETUA_RW->value)->firstOrFail();
        $roleWarga = Role::where('name', RoleName::WARGA->value)->firstOrFail();

        $this->ketuaRt01 = User::factory()->create([
            'role_id' => $roleRt->id,
            'email' => 'rt01_sec@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => '001',
            'status' => 'ACTIVE',
        ]);

        $this->ketuaRt02 = User::factory()->create([
            'role_id' => $roleRt->id,
            'email' => 'rt02_sec@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => '002',
            'status' => 'ACTIVE',
        ]);

        $this->bendaharaRw = User::factory()->create([
            'role_id' => $roleBendahara->id,
            'email' => 'bendahara_sec@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => null,
            'status' => 'ACTIVE',
        ]);

        $this->ketuaRw = User::factory()->create([
            'role_id' => $roleKetuaRw->id,
            'email' => 'ketuarw_sec@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => null,
            'status' => 'ACTIVE',
        ]);

        $this->wargaUser = User::factory()->create([
            'role_id' => $roleWarga->id,
            'email' => 'warga_sec@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => '001',
            'status' => 'ACTIVE',
        ]);

        $this->kkRt01 = KartuKeluarga::create([
            'no_kk' => '3216010101230012',
            'rt_code' => '001',
            'alamat_lengkap' => 'Jl. Mawar No. 12, RT 001',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ]);

        $this->kkRt02 = KartuKeluarga::create([
            'no_kk' => '3216010101230099',
            'rt_code' => '002',
            'alamat_lengkap' => 'Jl. Melati No. 99, RT 002',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ]);

        $this->iuranTypeIkk = IuranType::where('code', 'IKK')->firstOrFail();
    }

    /**
     * Test PII Sanitization: Audit Log tidak mengandung plaintext No. KK.
     */
    public function test_audit_log_does_not_contain_plaintext_no_kk(): void
    {
        Sanctum::actingAs($this->ketuaRt01);

        $this->postJson('/api/v1/catatan-iuran', [
            'no_kk' => '3216010101230012',
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
        ])->assertStatus(201);

        $auditLogs = AuditLog::where('module', 'Keuangan')->get();
        $this->assertNotEmpty($auditLogs);

        foreach ($auditLogs as $log) {
            $newValuesJson = json_encode($log->new_values);
            $this->assertStringNotContainsString('3216010101230012', (string) $newValuesJson);
            $this->assertArrayNotHasKey('no_kk', $log->new_values ?? []);
        }
    }

    /**
     * Test Area Scoping: Ketua RT tidak dapat melihat atau mencatat iuran wilayah RT lain.
     */
    public function test_area_scoping_ketua_rt_isolation(): void
    {
        // Catat iuran di RT 001
        $catatanRt01 = CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
            'recorded_by_user_id' => $this->ketuaRt01->id,
            'status' => StatusCatatanIuran::PENDING,
        ]);

        // Catat iuran di RT 002
        $catatanRt02 = CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt02->id,
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
            'recorded_by_user_id' => $this->ketuaRt02->id,
            'status' => StatusCatatanIuran::PENDING,
        ]);

        // Ketua RT 001 membuka list via API
        Sanctum::actingAs($this->ketuaRt01);
        $response = $this->getJson('/api/v1/catatan-iuran');

        $response->assertStatus(200);
        $items = $response->json('data');

        $this->assertCount(1, $items);
        $this->assertEquals($catatanRt01->iuran_id, $items[0]['iuran_id']);

        // Ketua RT 001 mencoba mencatat iuran untuk RT 002
        $forbidden = $this->postJson('/api/v1/catatan-iuran', [
            'no_kk' => '3216010101230099',
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
        ]);

        $forbidden->assertStatus(403);
    }

    /**
     * Test Anti-Self-Approval: Bendahara RW tidak dapat menyetujui pengeluaran kas sendiri.
     */
    public function test_anti_self_approval_kas_keluar(): void
    {
        $kas = KasKeluar::create([
            'kategori' => 'Kebersihan',
            'keterangan' => 'Beli kantong sampah besar',
            'nominal' => 100000.00,
            'tanggal_pengeluaran' => '2026-08-15',
            'recorded_by_user_id' => $this->bendaharaRw->id,
            'status' => StatusKasKeluar::PENDING,
        ]);

        Sanctum::actingAs($this->bendaharaRw);

        $response = $this->patchJson("/api/v1/kas-keluar/{$kas->id}/approve", [
            'action' => 'APPROVE',
        ]);

        $response->assertStatus(403);
        $this->assertEquals(StatusKasKeluar::PENDING, $kas->fresh()->status);
    }

    /**
     * Test Final State Immutability: Transaksi yang sudah APPROVED/REJECTED tidak dapat diproses ulang.
     */
    public function test_final_state_immutability_catatan_iuran(): void
    {
        $catatan = CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
            'recorded_by_user_id' => $this->ketuaRt01->id,
            'approved_by_user_id' => $this->bendaharaRw->id,
            'approved_at' => now(),
            'status' => StatusCatatanIuran::APPROVED,
        ]);

        Sanctum::actingAs($this->bendaharaRw);

        $response = $this->patchJson("/api/v1/catatan-iuran/{$catatan->iuran_id}/approve", [
            'action' => 'REJECT',
            'rejection_notes' => 'Mencoba reject transaksi yang sudah approved',
        ]);

        $response->assertStatus(409);
    }

    /**
     * Negative Authorization Matrix: Catat Iuran
     * KETUA_RT (allowed), BENDAHARA_RW (403), KETUA_RW (403), SUPER_ADMIN (403), WARGA (403).
     */
    public function test_catat_iuran_strict_rbac_matrix(): void
    {
        $roleSuperAdmin = Role::where('name', RoleName::SUPER_ADMIN->value)->firstOrFail();
        $superAdmin = User::factory()->create([
            'role_id' => $roleSuperAdmin->id,
            'email' => 'superadmin_sec@rw047.id',
            'password' => Hash::make('Password123!'),
            'status' => 'ACTIVE',
        ]);

        $payload = [
            'no_kk' => '3216010101230012',
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
        ];

        // 1. BENDAHARA_RW -> 403
        Sanctum::actingAs($this->bendaharaRw);
        $this->postJson('/api/v1/catatan-iuran', $payload)->assertStatus(403);

        // 2. KETUA_RW -> 403
        Sanctum::actingAs($this->ketuaRw);
        $this->postJson('/api/v1/catatan-iuran', $payload)->assertStatus(403);

        // 3. SUPER_ADMIN -> 403
        Sanctum::actingAs($superAdmin);
        $this->postJson('/api/v1/catatan-iuran', $payload)->assertStatus(403);

        // 4. WARGA -> 403
        Sanctum::actingAs($this->wargaUser);
        $this->postJson('/api/v1/catatan-iuran', $payload)->assertStatus(403);

        // 5. KETUA_RT (wilayah sama) -> 201 Created
        Sanctum::actingAs($this->ketuaRt01);
        $this->postJson('/api/v1/catatan-iuran', $payload)->assertStatus(201);
    }

    /**
     * Negative Authorization Matrix: Approve Iuran
     * BENDAHARA_RW (allowed), KETUA_RT (403), KETUA_RW (403), SUPER_ADMIN (403), WARGA (403).
     */
    public function test_approve_iuran_strict_rbac_matrix(): void
    {
        $roleSuperAdmin = Role::where('name', RoleName::SUPER_ADMIN->value)->firstOrFail();
        $superAdmin = User::factory()->create([
            'role_id' => $roleSuperAdmin->id,
            'email' => 'superadmin_sec2@rw047.id',
            'password' => Hash::make('Password123!'),
            'status' => 'ACTIVE',
        ]);

        $catatan = CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
            'recorded_by_user_id' => $this->ketuaRt01->id,
            'status' => StatusCatatanIuran::PENDING,
        ]);

        $payload = ['action' => 'APPROVE'];

        // 1. KETUA_RT -> 403
        Sanctum::actingAs($this->ketuaRt01);
        $this->patchJson("/api/v1/catatan-iuran/{$catatan->iuran_id}/approve", $payload)->assertStatus(403);

        // 2. KETUA_RW -> 403
        Sanctum::actingAs($this->ketuaRw);
        $this->patchJson("/api/v1/catatan-iuran/{$catatan->iuran_id}/approve", $payload)->assertStatus(403);

        // 3. SUPER_ADMIN -> 403
        Sanctum::actingAs($superAdmin);
        $this->patchJson("/api/v1/catatan-iuran/{$catatan->iuran_id}/approve", $payload)->assertStatus(403);

        // 4. WARGA -> 403
        Sanctum::actingAs($this->wargaUser);
        $this->patchJson("/api/v1/catatan-iuran/{$catatan->iuran_id}/approve", $payload)->assertStatus(403);

        // 5. BENDAHARA_RW -> 200 OK
        Sanctum::actingAs($this->bendaharaRw);
        $this->patchJson("/api/v1/catatan-iuran/{$catatan->iuran_id}/approve", $payload)->assertStatus(200);
    }

    /**
     * Negative Authorization Matrix: Catat Kas Keluar
     * BENDAHARA_RW (allowed), KETUA_RT (403), KETUA_RW (403), SUPER_ADMIN (403), WARGA (403).
     */
    public function test_catat_kas_keluar_strict_rbac_matrix(): void
    {
        $roleSuperAdmin = Role::where('name', RoleName::SUPER_ADMIN->value)->firstOrFail();
        $superAdmin = User::factory()->create([
            'role_id' => $roleSuperAdmin->id,
            'email' => 'superadmin_sec3@rw047.id',
            'password' => Hash::make('Password123!'),
            'status' => 'ACTIVE',
        ]);

        $payload = [
            'kategori' => 'Kebersihan Lingkungan',
            'keterangan' => 'Pembelian kantong sampah besar kerja bakti',
            'nominal' => 150000.00,
            'tanggal_pengeluaran' => '2026-08-15',
        ];

        // 1. KETUA_RT -> 403
        Sanctum::actingAs($this->ketuaRt01);
        $this->postJson('/api/v1/kas-keluar', $payload)->assertStatus(403);

        // 2. KETUA_RW -> 403
        Sanctum::actingAs($this->ketuaRw);
        $this->postJson('/api/v1/kas-keluar', $payload)->assertStatus(403);

        // 3. SUPER_ADMIN -> 403
        Sanctum::actingAs($superAdmin);
        $this->postJson('/api/v1/kas-keluar', $payload)->assertStatus(403);

        // 4. WARGA -> 403
        Sanctum::actingAs($this->wargaUser);
        $this->postJson('/api/v1/kas-keluar', $payload)->assertStatus(403);

        // 5. BENDAHARA_RW -> 201 Created
        Sanctum::actingAs($this->bendaharaRw);
        $this->postJson('/api/v1/kas-keluar', $payload)->assertStatus(201);
    }

    /**
     * Negative Authorization Matrix: Approve Kas Keluar
     * KETUA_RW (allowed), KETUA_RT (403), BENDAHARA_RW (403), SUPER_ADMIN (403), WARGA (403).
     */
    public function test_approve_kas_keluar_strict_rbac_matrix(): void
    {
        $roleSuperAdmin = Role::where('name', RoleName::SUPER_ADMIN->value)->firstOrFail();
        $superAdmin = User::factory()->create([
            'role_id' => $roleSuperAdmin->id,
            'email' => 'superadmin_sec4@rw047.id',
            'password' => Hash::make('Password123!'),
            'status' => 'ACTIVE',
        ]);

        $kas = KasKeluar::create([
            'kategori' => 'Kebersihan',
            'keterangan' => 'Honor petugas kebersihan RW',
            'nominal' => 200000.00,
            'tanggal_pengeluaran' => '2026-08-15',
            'recorded_by_user_id' => $this->bendaharaRw->id,
            'status' => StatusKasKeluar::PENDING,
        ]);

        $payload = ['action' => 'APPROVE'];

        // 1. KETUA_RT -> 403
        Sanctum::actingAs($this->ketuaRt01);
        $this->patchJson("/api/v1/kas-keluar/{$kas->id}/approve", $payload)->assertStatus(403);

        // 2. BENDAHARA_RW (recorded_by / non-Ketua RW) -> 403
        Sanctum::actingAs($this->bendaharaRw);
        $this->patchJson("/api/v1/kas-keluar/{$kas->id}/approve", $payload)->assertStatus(403);

        // 3. SUPER_ADMIN -> 403
        Sanctum::actingAs($superAdmin);
        $this->patchJson("/api/v1/kas-keluar/{$kas->id}/approve", $payload)->assertStatus(403);

        // 4. WARGA -> 403
        Sanctum::actingAs($this->wargaUser);
        $this->patchJson("/api/v1/kas-keluar/{$kas->id}/approve", $payload)->assertStatus(403);

        // 5. KETUA_RW -> 200 OK
        Sanctum::actingAs($this->ketuaRw);
        $this->patchJson("/api/v1/kas-keluar/{$kas->id}/approve", $payload)->assertStatus(200);
    }
}
