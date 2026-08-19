<?php

declare(strict_types=1);

namespace Tests\Feature\Keuangan;

use App\Enums\RoleName;
use App\Enums\StatusKasKeluar;
use App\Models\KasKeluar;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KasKeluarEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $bendaharaRw;

    private User $ketuaRw;

    private User $ketuaRt01;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $roleRt = Role::where('name', RoleName::KETUA_RT->value)->firstOrFail();
        $roleBendahara = Role::where('name', RoleName::BENDAHARA_RW->value)->firstOrFail();
        $roleKetuaRw = Role::where('name', RoleName::KETUA_RW->value)->firstOrFail();

        $this->bendaharaRw = User::factory()->create([
            'role_id' => $roleBendahara->id,
            'email' => 'bendahara_kas_api@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => null,
            'status' => 'ACTIVE',
        ]);

        $this->ketuaRw = User::factory()->create([
            'role_id' => $roleKetuaRw->id,
            'email' => 'ketuarw_kas_api@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => null,
            'status' => 'ACTIVE',
        ]);

        $this->ketuaRt01 = User::factory()->create([
            'role_id' => $roleRt->id,
            'email' => 'rt01_kas_api@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => '001',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_post_kas_keluar_success(): void
    {
        Sanctum::actingAs($this->bendaharaRw);

        $response = $this->postJson('/api/v1/kas-keluar', [
            'kategori' => 'Kebersihan Lingkungan',
            'keterangan' => 'Pembelian kantong sampah besar dan peralatan kerja bakti RW 047',
            'nominal' => 350000.00,
            'tanggal_pengeluaran' => '2026-08-15',
            'bukti_path' => 'uploads/bukti/kuitansi-sampah-20260815.jpg',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Pencatatan pengeluaran kas berhasil disimpan, menunggu persetujuan Ketua RW',
                'data' => [
                    'kategori' => 'Kebersihan Lingkungan',
                    'nominal' => 350000.00,
                    'status' => 'PENDING',
                ],
            ]);

        $this->assertDatabaseHas('kas_keluars', [
            'kategori' => 'Kebersihan Lingkungan',
            'nominal' => 350000.00,
            'status' => 'PENDING',
        ]);
    }

    public function test_post_kas_keluar_unauthorized_for_rt(): void
    {
        Sanctum::actingAs($this->ketuaRt01);

        $response = $this->postJson('/api/v1/kas-keluar', [
            'kategori' => 'Kebersihan',
            'keterangan' => 'Pembelian kantong sampah RT',
            'nominal' => 50000.00,
            'tanggal_pengeluaran' => '2026-08-15',
        ]);

        $response->assertStatus(403);
    }

    public function test_get_kas_keluar_list(): void
    {
        KasKeluar::create([
            'kategori' => 'Kebersihan',
            'keterangan' => 'Pembelian peralatan kerja bakti',
            'nominal' => 200000.00,
            'tanggal_pengeluaran' => '2026-08-15',
            'recorded_by_user_id' => $this->bendaharaRw->id,
            'status' => StatusKasKeluar::PENDING,
        ]);

        Sanctum::actingAs($this->ketuaRw);

        $response = $this->getJson('/api/v1/kas-keluar');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daftar pengeluaran kas berhasil diambil',
            ])
            ->assertJsonCount(1, 'data');
    }

    public function test_patch_approve_kas_keluar_success(): void
    {
        $kas = KasKeluar::create([
            'kategori' => 'Kebersihan',
            'keterangan' => 'Pembelian peralatan kerja bakti',
            'nominal' => 200000.00,
            'tanggal_pengeluaran' => '2026-08-15',
            'recorded_by_user_id' => $this->bendaharaRw->id,
            'status' => StatusKasKeluar::PENDING,
        ]);

        Sanctum::actingAs($this->ketuaRw);

        $response = $this->patchJson("/api/v1/kas-keluar/{$kas->id}/approve", [
            'action' => 'APPROVE',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Transaksi pengeluaran kas berhasil disetujui',
                'data' => [
                    'id' => $kas->id,
                    'status' => 'APPROVED',
                ],
            ]);
    }

    public function test_patch_approve_kas_keluar_anti_self_approval(): void
    {
        $kas = KasKeluar::create([
            'kategori' => 'Kebersihan',
            'keterangan' => 'Pembelian peralatan kerja bakti',
            'nominal' => 200000.00,
            'tanggal_pengeluaran' => '2026-08-15',
            'recorded_by_user_id' => $this->bendaharaRw->id,
            'status' => StatusKasKeluar::PENDING,
        ]);

        // Bendahara mencoba approve pencatatannya sendiri
        Sanctum::actingAs($this->bendaharaRw);

        $response = $this->patchJson("/api/v1/kas-keluar/{$kas->id}/approve", [
            'action' => 'APPROVE',
        ]);

        $response->assertStatus(403);
    }
}
