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
use Tests\TestCase;

class KeuanganWebTest extends TestCase
{
    use RefreshDatabase;

    private User $ketuaRt01;

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
            'email' => 'rt01_web@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => '001',
            'status' => 'ACTIVE',
        ]);

        $this->bendaharaRw = User::factory()->create([
            'role_id' => $roleBendahara->id,
            'email' => 'bendahara_web@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => null,
            'status' => 'ACTIVE',
        ]);

        $this->ketuaRw = User::factory()->create([
            'role_id' => $roleKetuaRw->id,
            'email' => 'ketuarw_web@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => null,
            'status' => 'ACTIVE',
        ]);

        $this->wargaUser = User::factory()->create([
            'role_id' => $roleWarga->id,
            'email' => 'warga_web@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => '001',
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

    public function test_guest_redirected_from_keuangan_routes(): void
    {
        $this->get('/keuangan/iuran')->assertRedirect('/login');
        $this->get('/keuangan/kas-keluar')->assertRedirect('/login');
        $this->get('/keuangan/rekap')->assertRedirect('/login');
    }

    public function test_pengurus_can_access_iuran_index(): void
    {
        $this->actingAs($this->ketuaRt01)
            ->get('/keuangan/iuran')
            ->assertStatus(200)
            ->assertSee('Iuran Warga');
    }

    public function test_ketua_rt_can_access_create_iuran_form(): void
    {
        $this->actingAs($this->ketuaRt01)
            ->get('/keuangan/iuran/create')
            ->assertStatus(200)
            ->assertSee('Catat Iuran Warga');
    }

    public function test_ketua_rt_can_submit_create_iuran(): void
    {
        $this->actingAs($this->ketuaRt01)
            ->post('/keuangan/iuran', [
                'no_kk' => '3216010101230012',
                'iuran_type_id' => $this->iuranTypeIkk->id,
                'nominal' => 50000.00,
                'periode_bulan' => 8,
                'periode_tahun' => 2026,
            ])
            ->assertRedirect(route('keuangan.iuran.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('catatan_iurans', [
            'kartu_keluarga_id' => $this->kkRt01->id,
            'status' => 'PENDING',
        ]);
    }

    public function test_bendahara_can_access_approval_iuran(): void
    {
        $this->actingAs($this->bendaharaRw)
            ->get('/keuangan/iuran/approval')
            ->assertStatus(200)
            ->assertSee('Verifikasi Iuran Warga');
    }

    public function test_bendahara_can_approve_iuran(): void
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

        $this->actingAs($this->bendaharaRw)
            ->post("/keuangan/iuran/{$catatan->iuran_id}/approve", [
                'action' => 'APPROVE',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(StatusCatatanIuran::APPROVED, $catatan->fresh()->status);
    }

    public function test_bendahara_can_record_kas_keluar(): void
    {
        $this->actingAs($this->bendaharaRw)
            ->post('/keuangan/kas-keluar', [
                'kategori' => 'Kebersihan Lingkungan',
                'keterangan' => 'Beli kantong sampah kerja bakti',
                'nominal' => 150000.00,
                'tanggal_pengeluaran' => '2026-08-15',
            ])
            ->assertRedirect(route('keuangan.kas-keluar.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('kas_keluars', [
            'kategori' => 'Kebersihan Lingkungan',
            'status' => 'PENDING',
        ]);
    }

    public function test_ketua_rw_can_approve_kas_keluar(): void
    {
        $kas = KasKeluar::create([
            'kategori' => 'Kebersihan',
            'keterangan' => 'Honor petugas kebersihan RW',
            'nominal' => 200000.00,
            'tanggal_pengeluaran' => '2026-08-15',
            'recorded_by_user_id' => $this->bendaharaRw->id,
            'status' => StatusKasKeluar::PENDING,
        ]);

        $this->actingAs($this->ketuaRw)
            ->post("/keuangan/kas-keluar/{$kas->id}/approve", [
                'action' => 'APPROVE',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(StatusKasKeluar::APPROVED, $kas->fresh()->status);
    }

    public function test_pengurus_can_access_rekap_page(): void
    {
        $this->actingAs($this->bendaharaRw)
            ->get('/keuangan/rekap')
            ->assertStatus(200)
            ->assertSee('Rekapitulasi Keuangan RW')
            ->assertSee('Posisi Saldo Riil');
    }
}
