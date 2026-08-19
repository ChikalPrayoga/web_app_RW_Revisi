<?php

declare(strict_types=1);

namespace Tests\Feature\Keuangan;

use App\Enums\RoleName;
use App\Enums\StatusCatatanIuran;
use App\Enums\StatusKasKeluar;
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

class KeuanganReportEndpointTest extends TestCase
{
    use RefreshDatabase;

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

        $roleBendahara = Role::where('name', RoleName::BENDAHARA_RW->value)->firstOrFail();
        $roleKetuaRw = Role::where('name', RoleName::KETUA_RW->value)->firstOrFail();
        $roleWarga = Role::where('name', RoleName::WARGA->value)->firstOrFail();

        $this->bendaharaRw = User::factory()->create([
            'role_id' => $roleBendahara->id,
            'email' => 'bendahara_rep@rw047.id',
            'password' => Hash::make('Password123!'),
            'status' => 'ACTIVE',
        ]);

        $this->ketuaRw = User::factory()->create([
            'role_id' => $roleKetuaRw->id,
            'email' => 'ketuarw_rep@rw047.id',
            'password' => Hash::make('Password123!'),
            'status' => 'ACTIVE',
        ]);

        $this->wargaUser = User::factory()->create([
            'role_id' => $roleWarga->id,
            'email' => 'warga_rep@rw047.id',
            'password' => Hash::make('Password123!'),
            'status' => 'ACTIVE',
        ]);

        $this->kkRt01 = KartuKeluarga::create([
            'no_kk' => '3216010101230012',
            'rt_code' => '001',
            'alamat_lengkap' => 'Jl. Mawar No. 12',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ]);

        $this->iuranTypeIkk = IuranType::where('code', 'IKK')->firstOrFail();
    }

    public function test_get_iuran_types_endpoint(): void
    {
        Sanctum::actingAs($this->bendaharaRw);

        $response = $this->getJson('/api/v1/iuran-types');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Data jenis iuran berhasil diambil',
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_get_rekapitulasi_gabungan_endpoint(): void
    {
        // 1. Catat iuran APPROVED
        CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 4900000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
            'recorded_by_user_id' => $this->bendaharaRw->id,
            'approved_by_user_id' => $this->bendaharaRw->id,
            'approved_at' => now(),
            'status' => StatusCatatanIuran::APPROVED,
        ]);

        // 2. Catat kas keluar APPROVED
        KasKeluar::create([
            'kategori' => 'Kebersihan Lingkungan',
            'keterangan' => 'Pembelian kantong sampah',
            'nominal' => 350000.00,
            'tanggal_pengeluaran' => '2026-08-15',
            'recorded_by_user_id' => $this->bendaharaRw->id,
            'approved_by_user_id' => $this->ketuaRw->id,
            'approved_at' => now(),
            'status' => StatusKasKeluar::APPROVED,
        ]);

        Sanctum::actingAs($this->bendaharaRw);

        $response = $this->getJson('/api/v1/keuangan/rekapitulasi?periode_bulan=8&periode_tahun=2026');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Rekapitulasi keuangan gabungan berhasil diambil',
                'data' => [
                    'periode' => '2026-08',
                    'total_pemasukan' => 4900000.00,
                    'total_pengeluaran' => 350000.00,
                    'saldo_akhir' => 4550000.00,
                ],
            ]);
    }

    public function test_rekapitulasi_gabungan_unauthorized_warga(): void
    {
        Sanctum::actingAs($this->wargaUser);

        $response = $this->getJson('/api/v1/keuangan/rekapitulasi?periode_bulan=8&periode_tahun=2026');

        $response->assertStatus(403);
    }
}
