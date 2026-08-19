<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Enums\JenisSurat;
use App\Enums\RoleName;
use App\Enums\StatusCatatanIuran;
use App\Enums\StatusKasKeluar;
use App\Enums\StatusPengajuanSurat;
use App\Enums\VerificationStatus;
use App\Models\CatatanIuran;
use App\Models\IuranType;
use App\Models\KartuKeluarga;
use App\Models\KasKeluar;
use App\Models\PengajuanSurat;
use App\Models\Role;
use App\Models\User;
use App\Models\Warga;
use Database\Seeders\IuranTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature Test untuk Web Controller Dashboard (GET /dashboard).
 *
 * @see USER_STORIES.md US-DASH-01
 * @see UI_UX_SPECIFICATION.md §2.2a
 */
class DashboardWebTest extends TestCase
{
    use RefreshDatabase;

    private User $ketuaRt01;

    private User $sekretarisRw;

    private User $bendaharaRw;

    private User $ketuaRw;

    private User $superAdmin;

    private User $wargaUser;

    private KartuKeluarga $kkRt01;

    private Warga $wargaRt01;

    private string $rawNoKk = '3216010101230099';

    private string $rawNik = '3216011505900099';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(IuranTypeSeeder::class);

        $roleRt = Role::where('name', RoleName::KETUA_RT->value)->firstOrFail();
        $roleSekretaris = Role::where('name', RoleName::SEKRETARIS_RW->value)->firstOrFail();
        $roleBendahara = Role::where('name', RoleName::BENDAHARA_RW->value)->firstOrFail();
        $roleKetuaRw = Role::where('name', RoleName::KETUA_RW->value)->firstOrFail();
        $roleSuperAdmin = Role::where('name', RoleName::SUPER_ADMIN->value)->firstOrFail();
        $roleWarga = Role::where('name', RoleName::WARGA->value)->firstOrFail();

        $this->ketuaRt01 = User::factory()->create([
            'role_id' => $roleRt->id,
            'full_name' => 'Pak RT Satu',
            'rt_code' => '001',
            'status' => 'ACTIVE',
        ]);

        $this->sekretarisRw = User::factory()->create([
            'role_id' => $roleSekretaris->id,
            'full_name' => 'Ibu Sekretaris RW',
            'status' => 'ACTIVE',
        ]);

        $this->bendaharaRw = User::factory()->create([
            'role_id' => $roleBendahara->id,
            'full_name' => 'Ibu Bendahara RW',
            'status' => 'ACTIVE',
        ]);

        $this->ketuaRw = User::factory()->create([
            'role_id' => $roleKetuaRw->id,
            'full_name' => 'Bapak Ketua RW 047',
            'status' => 'ACTIVE',
        ]);

        $this->superAdmin = User::factory()->create([
            'role_id' => $roleSuperAdmin->id,
            'full_name' => 'Administrator Sistem',
            'status' => 'ACTIVE',
        ]);

        $this->wargaUser = User::factory()->create([
            'role_id' => $roleWarga->id,
            'full_name' => 'Warga Biasa',
            'status' => 'ACTIVE',
        ]);

        $this->kkRt01 = KartuKeluarga::create([
            'no_kk' => $this->rawNoKk,
            'rt_code' => '001',
            'alamat_lengkap' => 'Jl. Kenanga No. 10',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ]);

        $this->wargaRt01 = Warga::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'nik' => $this->rawNik,
            'no_kk' => $this->rawNoKk,
            'nama_lengkap' => 'Warga RT 001',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1995-01-01',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
            'status_warga' => 'TETAP',
            'verification_status' => 'TERVERIFIKASI',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_warga_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->wargaUser)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Selamat datang, Warga Biasa');
        $response->assertSee('Ajukan Surat Pengantar / SKD');
    }

    public function test_ketua_rt_can_view_dashboard_with_scoped_data(): void
    {
        // Add pending surat in RT 001
        PengajuanSurat::create([
            'tracking_code' => 'SRT-20260818-WEB001',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Urus Surat KTP',
            'current_status' => StatusPengajuanSurat::SUBMITTED->value,
            'tanggal_pengajuan' => now(),
        ]);

        $response = $this->actingAs($this->ketuaRt01)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Selamat datang, Pak RT Satu');
        $response->assertSee('Wilayah RT 001');
        $response->assertSee('Butuh Tindakan Anda');
        $response->assertSee('Review Pengajuan Surat');
    }

    public function test_sekretaris_rw_can_view_dashboard_with_rw_items(): void
    {
        // Add warga pending verification
        $nikPending = '3216011505900088';
        Warga::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'nik' => $nikPending,
            'no_kk' => $this->rawNoKk,
            'nama_lengkap' => 'Warga Baru Menunggu Verifikasi',
            'jenis_kelamin' => 'P',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '1998-05-10',
            'status_hubungan_keluarga' => 'Anak',
            'status_warga' => 'TETAP',
            'verification_status' => VerificationStatus::MENUNGGU_VERIFIKASI->value,
        ]);

        $response = $this->actingAs($this->sekretarisRw)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Ibu Sekretaris RW');
        $response->assertSee('Verifikasi Data Warga Baru');
    }

    public function test_bendahara_rw_can_view_dashboard_with_financial_action_items(): void
    {
        $iuranType = IuranType::firstOrFail();

        CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'iuran_type_id' => $iuranType->id,
            'periode_bulan' => (int) now()->format('n'),
            'periode_tahun' => (int) now()->format('Y'),
            'nominal' => 60000.00,
            'status' => StatusCatatanIuran::PENDING->value,
            'recorded_by_user_id' => $this->ketuaRt01->id,
        ]);

        $response = $this->actingAs($this->bendaharaRw)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Ibu Bendahara RW');
        $response->assertSee('Persetujuan Iuran');
        $response->assertSee('Saldo Kas RW 047');
    }

    public function test_ketua_rw_can_view_dashboard_with_approval_items(): void
    {
        KasKeluar::create([
            'judul_pengeluaran' => 'Beli Lampu Penerangan Jalan',
            'kategori' => 'SARANA_PRASARANA',
            'nominal' => 150000.00,
            'tanggal_pengeluaran' => now(),
            'keterangan' => 'Penggantian lampu pos ronda',
            'status' => StatusKasKeluar::PENDING->value,
            'recorded_by_user_id' => $this->bendaharaRw->id,
        ]);

        $response = $this->actingAs($this->ketuaRw)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Bapak Ketua RW 047');
        $response->assertSee('Persetujuan Pengeluaran Kas');
    }

    public function test_empty_action_center_shows_all_tasks_completed(): void
    {
        $response = $this->actingAs($this->ketuaRt01)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Semua Tugas Selesai!');
    }
}
