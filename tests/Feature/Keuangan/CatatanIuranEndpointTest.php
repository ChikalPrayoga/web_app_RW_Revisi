<?php

declare(strict_types=1);

namespace Tests\Feature\Keuangan;

use App\Enums\RoleName;
use App\Enums\StatusCatatanIuran;
use App\Models\CatatanIuran;
use App\Models\IuranType;
use App\Models\KartuKeluarga;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\IuranTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CatatanIuranEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $ketuaRt01;

    private User $ketuaRt02;

    private User $bendaharaRw;

    private User $ketuaRw;

    private User $wargaUser;

    private KartuKeluarga $kkRt01;

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
            'email' => 'rt01_api@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => '001',
            'status' => 'ACTIVE',
        ]);

        $this->ketuaRt02 = User::factory()->create([
            'role_id' => $roleRt->id,
            'email' => 'rt02_api@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => '002',
            'status' => 'ACTIVE',
        ]);

        $this->bendaharaRw = User::factory()->create([
            'role_id' => $roleBendahara->id,
            'email' => 'bendahara_api@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => null,
            'status' => 'ACTIVE',
        ]);

        $this->ketuaRw = User::factory()->create([
            'role_id' => $roleKetuaRw->id,
            'email' => 'ketuarw_api@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => null,
            'status' => 'ACTIVE',
        ]);

        $this->wargaUser = User::factory()->create([
            'role_id' => $roleWarga->id,
            'email' => 'warga_api@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => '001',
            'status' => 'ACTIVE',
        ]);

        $this->kkRt01 = KartuKeluarga::create([
            'no_kk' => '3216010101230012',
            'rt_code' => '001',
            'alamat_lengkap' => 'Jl. Anggrek No. 12, RT 001',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ]);

        $this->iuranTypeIkk = IuranType::where('code', 'IKK')->firstOrFail();
    }

    public function test_post_catatan_iuran_success(): void
    {
        Sanctum::actingAs($this->ketuaRt01);

        $response = $this->postJson('/api/v1/catatan-iuran', [
            'no_kk' => '3216010101230012',
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
            'tanggal_pembayaran' => '2026-08-10',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Pencatatan iuran berhasil disimpan, menunggu persetujuan Bendahara RW',
                'data' => [
                    'no_kk_masked' => '3216xxxxxxxx0012',
                    'nominal' => 50000.00,
                    'periode_bulan' => 8,
                    'periode_tahun' => 2026,
                    'status' => 'PENDING',
                ],
            ]);

        $this->assertDatabaseHas('catatan_iurans', [
            'kartu_keluarga_id' => $this->kkRt01->id,
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
            'status' => 'PENDING',
        ]);
    }

    public function test_post_catatan_iuran_unauthorized_role(): void
    {
        Sanctum::actingAs($this->wargaUser);

        $response = $this->postJson('/api/v1/catatan-iuran', [
            'no_kk' => '3216010101230012',
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
        ]);

        $response->assertStatus(403);
    }

    public function test_post_catatan_iuran_duplicate_conflict_409(): void
    {
        Sanctum::actingAs($this->ketuaRt01);

        $this->postJson('/api/v1/catatan-iuran', [
            'no_kk' => '3216010101230012',
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
        ])->assertStatus(201);

        // Duplicate submission -> 409 Conflict
        $response = $this->postJson('/api/v1/catatan-iuran', [
            'no_kk' => '3216010101230012',
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Iuran untuk KK ini pada periode Agustus 2026 sudah tercatat sebelumnya',
            ]);
    }

    public function test_patch_approve_iuran_success(): void
    {
        $catatan = CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
            'recorded_by_user_id' => $this->ketuaRt01->id,
            'status' => StatusCatatanIuran::PENDING,
        ]);

        Sanctum::actingAs($this->bendaharaRw);

        $response = $this->patchJson("/api/v1/catatan-iuran/{$catatan->iuran_id}/approve", [
            'action' => 'APPROVE',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Transaksi iuran berhasil disetujui',
                'data' => [
                    'iuran_id' => $catatan->iuran_id,
                    'status' => 'APPROVED',
                ],
            ]);
    }

    public function test_patch_reject_iuran_success(): void
    {
        $catatan = CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
            'recorded_by_user_id' => $this->ketuaRt01->id,
            'status' => StatusCatatanIuran::PENDING,
        ]);

        Sanctum::actingAs($this->bendaharaRw);

        $response = $this->patchJson("/api/v1/catatan-iuran/{$catatan->iuran_id}/approve", [
            'action' => 'REJECT',
            'rejection_notes' => 'Nominal tidak sesuai bukti transfer',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Transaksi iuran berhasil ditolak',
                'data' => [
                    'iuran_id' => $catatan->iuran_id,
                    'status' => 'REJECTED',
                ],
            ]);
    }

    public function test_get_catatan_iuran_rekapitulasi(): void
    {
        CatatanIuran::create([
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

        $response = $this->getJson('/api/v1/catatan-iuran/rekapitulasi?periode_bulan=8&periode_tahun=2026');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Rekapitulasi iuran berhasil diambil',
                'data' => [
                    'periode' => '2026-08',
                    'total_kk_wajib_bayar' => 1,
                    'total_kk_sudah_bayar' => 1,
                    'total_nominal_terkumpul' => 50000.00,
                ],
            ]);
    }
}
