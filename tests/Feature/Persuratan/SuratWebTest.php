<?php

declare(strict_types=1);

namespace Tests\Feature\Persuratan;

use App\Enums\JenisSurat;
use App\Enums\ReviewAction;
use App\Enums\RoleName;
use App\Enums\StatusPengajuanSurat;
use App\Models\KartuKeluarga;
use App\Models\PengajuanSurat;
use App\Models\Role;
use App\Models\User;
use App\Models\Warga;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature Test untuk Web routes Persuratan (Blade).
 *
 * Mencakup: guest form, public tracking, pengurus list, forbidden access, verifikasi form.
 */
class SuratWebTest extends TestCase
{
    use RefreshDatabase;

    private User $ketuaRt01;

    private User $ketuaRt02;

    private User $sekretarisRw;

    private KartuKeluarga $kkRt01;

    private Warga $wargaRt01;

    private string $rawNik = '3216011505900021';

    private string $rawNoKk = '3216010101230012';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $roleRt = Role::where('name', RoleName::KETUA_RT->value)->firstOrFail();
        $roleSekretaris = Role::where('name', RoleName::SEKRETARIS_RW->value)->firstOrFail();

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
        $this->sekretarisRw = User::factory()->create([
            'role_id' => $roleSekretaris->id,
            'rt_code' => null,
            'status' => 'ACTIVE',
        ]);

        $this->kkRt01 = KartuKeluarga::create([
            'no_kk' => $this->rawNoKk,
            'rt_code' => '001',
            'alamat_lengkap' => 'Jl. Kenanga No. 10, RT 001',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ]);
        $this->wargaRt01 = Warga::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'nik' => $this->rawNik,
            'no_kk' => $this->rawNoKk,
            'nama_lengkap' => 'Budi Pratama',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1990-05-15',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
            'status_warga' => 'TETAP',
            'verification_status' => 'TERVERIFIKASI',
        ]);
    }

    // =========================================================================
    // PUBLIC — Guest Access
    // =========================================================================

    public function test_guest_dapat_mengakses_halaman_form_pengajuan(): void
    {
        $response = $this->get('/surat/ajukan');
        $response->assertOk()
            ->assertViewIs('persuratan.create');
    }

    public function test_guest_dapat_mengakses_halaman_tracking(): void
    {
        $response = $this->get('/surat/lacak');
        $response->assertOk()
            ->assertViewIs('persuratan.track');
    }

    public function test_guest_dapat_submit_form_pengajuan_dengan_data_valid(): void
    {
        $response = $this->post('/surat/ajukan', [
            '_token' => csrf_token(),
            'nik' => $this->rawNik,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Pengurusan administrasi perpanjangan KTP',
        ]);

        // Redirect ke halaman sukses
        $response->assertRedirect();
        $this->assertStringContainsString('/surat/sukses/', $response->headers->get('Location'));
    }

    public function test_form_pengajuan_menampilkan_error_validasi_nik_salah(): void
    {
        $response = $this->post('/surat/ajukan', [
            '_token' => csrf_token(),
            'nik' => '123',
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan test validasi',
        ]);

        $response->assertRedirect()
            ->assertSessionHasErrors(['nik']);
    }

    public function test_halaman_sukses_menampilkan_tracking_code(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-WEBTEST',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan web test success',
            'current_status' => StatusPengajuanSurat::SUBMITTED->value,
            'tanggal_pengajuan' => now(),
        ]);

        $response = $this->get('/surat/sukses/SRT-20260817-WEBTEST');
        $response->assertOk()
            ->assertSee('SRT-20260817-WEBTEST');
    }

    public function test_halaman_tracking_result_menampilkan_status_pengajuan(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-WEBTRK',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan tracking web test',
            'current_status' => StatusPengajuanSurat::SUBMITTED->value,
            'tanggal_pengajuan' => now(),
        ]);

        $response = $this->get('/surat/lacak/SRT-20260817-WEBTRK');
        $response->assertOk()
            ->assertSee('SRT-20260817-WEBTRK')
            ->assertViewIs('persuratan.track_result');
    }

    public function test_tracking_kode_tidak_ada_redirect_dengan_error(): void
    {
        $response = $this->get('/surat/lacak/SRT-TIDAK-ADA-SAMA-SEKALI');
        $response->assertRedirect('/surat/lacak')
            ->assertSessionHasErrors(['tracking_code']);
    }

    // =========================================================================
    // PROTECTED — Pengurus Access
    // =========================================================================

    public function test_pengurus_dapat_mengakses_daftar_pengajuan(): void
    {
        $response = $this->actingAs($this->ketuaRt01)
            ->get('/surat');

        $response->assertOk()
            ->assertViewIs('persuratan.index');
    }

    public function test_guest_tidak_dapat_akses_daftar_pengajuan_pengurus(): void
    {
        $response = $this->get('/surat');
        $response->assertRedirect('/login');
    }

    public function test_pengurus_dapat_mengakses_detail_pengajuan_wilayahnya(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-WEBSHOW',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan show detail web test',
            'current_status' => StatusPengajuanSurat::SUBMITTED->value,
            'tanggal_pengajuan' => now(),
        ]);

        $response = $this->actingAs($this->ketuaRt01)
            ->get("/surat/{$pengajuan->pengajuan_id}");

        $response->assertOk()
            ->assertViewIs('persuratan.show');
    }

    public function test_ketua_rt_tidak_dapat_akses_detail_pengajuan_rt_lain(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-WEBACC',
            'warga_id' => $this->wargaRt01->id, // Warga RT 001
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan forbidden access web test',
            'current_status' => StatusPengajuanSurat::SUBMITTED->value,
            'tanggal_pengajuan' => now(),
        ]);

        $response = $this->actingAs($this->ketuaRt02) // Ketua RT 002
            ->get("/surat/{$pengajuan->pengajuan_id}");

        $response->assertForbidden();
    }

    public function test_pengurus_dapat_mengakses_form_verifikasi(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-WEBVRF',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan verifikasi form web test',
            'current_status' => StatusPengajuanSurat::SUBMITTED->value,
            'tanggal_pengajuan' => now(),
        ]);

        $response = $this->actingAs($this->ketuaRt01)
            ->get("/surat/{$pengajuan->pengajuan_id}/verifikasi");

        $response->assertOk()
            ->assertViewIs('persuratan.verify');
    }

    public function test_ketua_rt_tidak_dapat_akses_form_verifikasi_rt_lain(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-WEBVRF2',
            'warga_id' => $this->wargaRt01->id, // Warga RT 001
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan verifikasi forbidden test',
            'current_status' => StatusPengajuanSurat::SUBMITTED->value,
            'tanggal_pengajuan' => now(),
        ]);

        $response = $this->actingAs($this->ketuaRt02) // Ketua RT 002
            ->get("/surat/{$pengajuan->pengajuan_id}/verifikasi");

        $response->assertForbidden();
    }

    public function test_web_verifikasi_sukses_redirect_ke_detail(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-WEBPOST',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan verifikasi post web test',
            'current_status' => StatusPengajuanSurat::SUBMITTED->value,
            'tanggal_pengajuan' => now(),
        ]);

        $response = $this->actingAs($this->ketuaRt01)
            ->post("/surat/{$pengajuan->pengajuan_id}/verifikasi", [
                '_token' => csrf_token(),
                'action' => ReviewAction::APPROVE->value,
            ]);

        $response->assertRedirect("/surat/{$pengajuan->pengajuan_id}")
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pengajuan_surats', [
            'pengajuan_id' => $pengajuan->pengajuan_id,
            'current_status' => 'RT_REVIEW',
        ]);
    }

    public function test_web_verifikasi_final_status_mengembalikan_error(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-WEBFIN',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan final status web test',
            'current_status' => StatusPengajuanSurat::COMPLETED->value,
            'nomor_surat' => '001/SP/RW047/08/2026',
            'tanggal_pengajuan' => now(),
            'tanggal_selesai' => now(),
        ]);

        $response = $this->actingAs($this->sekretarisRw)
            ->post("/surat/{$pengajuan->pengajuan_id}/verifikasi", [
                '_token' => csrf_token(),
                'action' => ReviewAction::APPROVE->value,
            ]);

        $response->assertRedirect("/surat/{$pengajuan->pengajuan_id}")
            ->assertSessionHas('error');
    }
}
