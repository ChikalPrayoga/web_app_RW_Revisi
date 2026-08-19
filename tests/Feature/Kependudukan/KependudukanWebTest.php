<?php

declare(strict_types=1);

namespace Tests\Feature\Kependudukan;

use App\Enums\RoleName;
use App\Enums\StatusWarga;
use App\Enums\VerificationStatus;
use App\Models\KartuKeluarga;
use App\Models\Role;
use App\Models\User;
use App\Models\Warga;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KependudukanWebTest extends TestCase
{
    use RefreshDatabase;

    private User $ketuaRt01;

    private User $ketuaRt02;

    private User $sekretarisRw;

    private User $ketuaRw;

    private User $wargaUser;

    private KartuKeluarga $kkRt01;

    private KartuKeluarga $kkRt02;

    private Warga $wargaRt01;

    private Warga $wargaRt02;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $roleRt = Role::where('name', RoleName::KETUA_RT->value)->firstOrFail();
        $roleSekretaris = Role::where('name', RoleName::SEKRETARIS_RW->value)->firstOrFail();
        $roleKetuaRw = Role::where('name', RoleName::KETUA_RW->value)->firstOrFail();
        $roleWarga = Role::where('name', RoleName::WARGA->value)->firstOrFail();

        $this->ketuaRt01 = User::factory()->create([
            'role_id' => $roleRt->id,
            'email' => 'rt01@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => '001',
            'status' => 'ACTIVE',
        ]);

        $this->ketuaRt02 = User::factory()->create([
            'role_id' => $roleRt->id,
            'email' => 'rt02@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => '002',
            'status' => 'ACTIVE',
        ]);

        $this->sekretarisRw = User::factory()->create([
            'role_id' => $roleSekretaris->id,
            'email' => 'sekretaris@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => null,
            'status' => 'ACTIVE',
        ]);

        $this->ketuaRw = User::factory()->create([
            'role_id' => $roleKetuaRw->id,
            'email' => 'ketuarw@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => null,
            'status' => 'ACTIVE',
        ]);

        $this->wargaUser = User::factory()->create([
            'role_id' => $roleWarga->id,
            'email' => 'warga@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => '001',
            'status' => 'ACTIVE',
        ]);

        $this->kkRt01 = KartuKeluarga::create([
            'no_kk' => '3216010101010001',
            'rt_code' => '001',
            'alamat_lengkap' => 'Jl. Mawar No. 1, RT 001',
            'blok' => 'A',
            'nomor_rumah' => '1',
            'status_kepemilikan_rumah' => 'MILIK SENDIRI',
        ]);

        $this->kkRt02 = KartuKeluarga::create([
            'no_kk' => '3216010101010002',
            'rt_code' => '002',
            'alamat_lengkap' => 'Jl. Melati No. 2, RT 002',
            'blok' => 'B',
            'nomor_rumah' => '2',
            'status_kepemilikan_rumah' => 'MILIK SENDIRI',
        ]);

        $this->wargaRt01 = Warga::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'nik' => '3216010101900001',
            'no_kk' => '3216010101010001',
            'nama_lengkap' => 'Budi Santoso RT01',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1990-01-01',
            'pekerjaan' => 'Karyawan',
            'nomor_hp' => '081234567891',
            'status_hubungan_keluarga' => 'KEPALA KELUARGA',
            'status_warga' => StatusWarga::TETAP->value,
            'verification_status' => VerificationStatus::MENUNGGU_VERIFIKASI->value,
        ]);

        $this->wargaRt02 = Warga::create([
            'kartu_keluarga_id' => $this->kkRt02->id,
            'nik' => '3216010101900002',
            'no_kk' => '3216010101010002',
            'nama_lengkap' => 'Siti Aminah RT02',
            'jenis_kelamin' => 'P',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1992-02-02',
            'pekerjaan' => 'Guru',
            'nomor_hp' => '081234567892',
            'status_hubungan_keluarga' => 'KEPALA KELUARGA',
            'status_warga' => StatusWarga::TETAP->value,
            'verification_status' => VerificationStatus::MENUNGGU_VERIFIKASI->value,
        ]);
    }

    public function test_guest_is_redirected_to_login_from_kependudukan_routes(): void
    {
        $this->get(route('kependudukan.warga.index'))->assertRedirect(route('login'));
        $this->get(route('kependudukan.warga.create'))->assertRedirect(route('login'));
        $this->get(route('kependudukan.warga.show', ['nik_hash' => $this->wargaRt01->nik_hash]))->assertRedirect(route('login'));
        $this->get(route('kependudukan.kk.index'))->assertRedirect(route('login'));
        $this->get(route('kependudukan.kk.create'))->assertRedirect(route('login'));
    }

    public function test_warga_role_cannot_access_kependudukan_web_views(): void
    {
        $this->actingAs($this->wargaUser);

        $this->get(route('kependudukan.warga.index'))->assertForbidden();
        $this->get(route('kependudukan.warga.create'))->assertForbidden();
        $this->get(route('kependudukan.kk.index'))->assertForbidden();
        $this->get(route('kependudukan.kk.create'))->assertForbidden();
    }

    public function test_ketua_rt_can_view_warga_index_scoped_to_own_rt(): void
    {
        $this->actingAs($this->ketuaRt01);

        $response = $this->get(route('kependudukan.warga.index'));

        $response->assertOk();
        $response->assertSee('Budi Santoso RT01');
        $response->assertDontSee('Siti Aminah RT02');
        $response->assertSee($this->wargaRt01->nik_masked);
        $response->assertDontSee('3216010101900001'); // Pastikan tidak leak plaintext NIK
    }

    public function test_ketua_rt_cannot_bypass_area_scoping_via_query_parameter(): void
    {
        $this->actingAs($this->ketuaRt01);

        $response = $this->get(route('kependudukan.warga.index', ['rt_code' => '002']));

        $response->assertOk();
        $response->assertSee('Budi Santoso RT01');
        $response->assertDontSee('Siti Aminah RT02');
    }

    public function test_sekretaris_rw_and_ketua_rw_can_view_all_warga(): void
    {
        $this->actingAs($this->sekretarisRw);

        $response = $this->get(route('kependudukan.warga.index'));

        $response->assertOk();
        $response->assertSee('Budi Santoso RT01');
        $response->assertSee('Siti Aminah RT02');
    }

    public function test_ketua_rt_can_view_warga_detail_in_own_rt(): void
    {
        $this->actingAs($this->ketuaRt01);

        $response = $this->get(route('kependudukan.warga.show', ['nik_hash' => $this->wargaRt01->nik_hash]));

        $response->assertOk();
        $response->assertSee('Budi Santoso RT01');
        $response->assertSee($this->wargaRt01->nik_masked);
        $response->assertSee($this->wargaRt01->no_kk_masked);
        $response->assertSee('Bekasi'); // Tempat lahir rendered securely on authorized detail
    }

    public function test_ketua_rt_viewing_warga_detail_in_other_rt_returns_403(): void
    {
        $this->actingAs($this->ketuaRt01);

        $response = $this->get(route('kependudukan.warga.show', ['nik_hash' => $this->wargaRt02->nik_hash]));

        $response->assertForbidden();
    }

    public function test_ketua_rt_can_render_create_warga_form(): void
    {
        $this->actingAs($this->ketuaRt01);

        $response = $this->get(route('kependudukan.warga.create'));

        $response->assertOk();
        $response->assertSee('Pendaftaran Data Warga Baru');
    }

    public function test_ketua_rt_can_create_warga_in_own_rt(): void
    {
        $this->actingAs($this->ketuaRt01);

        $payload = [
            'nik' => '3216010101950009',
            'no_kk' => '3216010101010001', // RT 001
            'nama_lengkap' => 'Ahmad Baru RT01',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1995-05-05',
            'pekerjaan' => 'Wiraswasta',
            'nomor_hp' => '081299998888',
            'status_hubungan_keluarga' => 'ANAK',
            'status_sosio_ekonomi' => 'MAMPU',
            'status_warga' => 'TETAP',
        ];

        $response = $this->post(route('kependudukan.warga.store'), $payload);

        $createdWarga = Warga::where('nama_lengkap', 'Ahmad Baru RT01')->firstOrFail();

        $response->assertRedirect(route('kependudukan.warga.show', ['nik_hash' => $createdWarga->nik_hash]));
        $this->assertEquals(VerificationStatus::MENUNGGU_VERIFIKASI->value, $createdWarga->verification_status);
        $this->assertEquals($this->kkRt01->id, $createdWarga->kartu_keluarga_id);
    }

    public function test_create_warga_duplicate_nik_returns_validation_error(): void
    {
        $this->actingAs($this->ketuaRt01);

        $payload = [
            'nik' => '3216010101900001', // Duplicate NIK dari $wargaRt01
            'no_kk' => '3216010101010001',
            'nama_lengkap' => 'Duplikat Warga',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1990-01-01',
            'status_hubungan_keluarga' => 'ANAK',
        ];

        $response = $this->post(route('kependudukan.warga.store'), $payload);

        $response->assertSessionHasErrors(['nik']);
    }

    public function test_ketua_rt_cannot_create_warga_in_other_rt_kk(): void
    {
        $this->actingAs($this->ketuaRt01);

        $payload = [
            'nik' => '3216010101950077',
            'no_kk' => '3216010101010002', // RT 002 KK
            'nama_lengkap' => 'Warga Lintas RT',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1995-05-05',
            'status_hubungan_keluarga' => 'ANAK',
        ];

        $response = $this->post(route('kependudukan.warga.store'), $payload);

        $response->assertForbidden();
    }

    public function test_ketua_rt_can_render_edit_warga_form_in_own_rt(): void
    {
        $this->actingAs($this->ketuaRt01);

        $response = $this->get(route('kependudukan.warga.edit', ['nik_hash' => $this->wargaRt01->nik_hash]));

        $response->assertOk();
        $response->assertSee('Perbarui Data Warga');
        $response->assertSee($this->wargaRt01->nama_lengkap);
    }

    public function test_ketua_rt_cannot_render_edit_warga_form_in_other_rt(): void
    {
        $this->actingAs($this->ketuaRt01);

        $response = $this->get(route('kependudukan.warga.edit', ['nik_hash' => $this->wargaRt02->nik_hash]));

        $response->assertForbidden();
    }

    public function test_ketua_rt_can_update_warga_in_own_rt_resets_verification_status(): void
    {
        $this->actingAs($this->ketuaRt01);

        // Ubah dulu ke TERVERIFIKASI
        $this->wargaRt01->update(['verification_status' => VerificationStatus::TERVERIFIKASI->value]);

        $payload = [
            'pekerjaan' => 'PNS',
            'nomor_hp' => '081200001111',
            'status_hubungan_keluarga' => 'KEPALA KELUARGA',
            'status_warga' => 'TETAP',
        ];

        $response = $this->patch(route('kependudukan.warga.update', ['nik_hash' => $this->wargaRt01->nik_hash]), $payload);

        $response->assertRedirect(route('kependudukan.warga.show', ['nik_hash' => $this->wargaRt01->nik_hash]));
        $this->wargaRt01->refresh();
        $this->assertEquals('PNS', $this->wargaRt01->pekerjaan);
        $this->assertEquals(VerificationStatus::MENUNGGU_VERIFIKASI->value, $this->wargaRt01->verification_status);
    }

    public function test_sekretaris_rw_can_render_verify_warga_form(): void
    {
        $this->actingAs($this->sekretarisRw);

        $response = $this->get(route('kependudukan.warga.verify.form', ['nik_hash' => $this->wargaRt01->nik_hash]));

        $response->assertOk();
        $response->assertSee('Verifikasi Data Kependudukan');
    }

    public function test_non_sekretaris_rw_cannot_render_verify_warga_form(): void
    {
        $this->actingAs($this->ketuaRt01);

        $response = $this->get(route('kependudukan.warga.verify.form', ['nik_hash' => $this->wargaRt01->nik_hash]));

        $response->assertForbidden();
    }

    public function test_sekretaris_rw_can_verify_warga_approved(): void
    {
        $this->actingAs($this->sekretarisRw);

        $payload = [
            'decision' => 'APPROVED',
        ];

        $response = $this->post(route('kependudukan.warga.verify', ['nik_hash' => $this->wargaRt01->nik_hash]), $payload);

        $response->assertRedirect(route('kependudukan.warga.show', ['nik_hash' => $this->wargaRt01->nik_hash]));
        $this->wargaRt01->refresh();
        $this->assertEquals(VerificationStatus::TERVERIFIKASI->value, $this->wargaRt01->verification_status);
        $this->assertEquals($this->sekretarisRw->id, $this->wargaRt01->verified_by_user_id);
    }

    public function test_sekretaris_rw_can_verify_warga_rejected_with_notes(): void
    {
        $this->actingAs($this->sekretarisRw);

        $payload = [
            'decision' => 'REJECTED',
            'rejection_notes' => 'Foto dokumen pendukung tidak sesuai dengan data NIK',
        ];

        $response = $this->post(route('kependudukan.warga.verify', ['nik_hash' => $this->wargaRt01->nik_hash]), $payload);

        $response->assertRedirect(route('kependudukan.warga.show', ['nik_hash' => $this->wargaRt01->nik_hash]));
        $this->wargaRt01->refresh();
        $this->assertEquals(VerificationStatus::DITOLAK->value, $this->wargaRt01->verification_status);
        $this->assertEquals('Foto dokumen pendukung tidak sesuai dengan data NIK', $this->wargaRt01->verification_notes);
    }

    public function test_sekretaris_rw_verify_rejected_without_notes_fails_validation(): void
    {
        $this->actingAs($this->sekretarisRw);

        $payload = [
            'decision' => 'REJECTED',
            'rejection_notes' => '',
        ];

        $response = $this->post(route('kependudukan.warga.verify', ['nik_hash' => $this->wargaRt01->nik_hash]), $payload);

        $response->assertSessionHasErrors(['rejection_notes']);
    }

    public function test_verify_warga_already_verified_returns_error_flash(): void
    {
        $this->actingAs($this->sekretarisRw);

        $this->wargaRt01->update(['verification_status' => VerificationStatus::TERVERIFIKASI->value]);

        $payload = [
            'decision' => 'APPROVED',
        ];

        $response = $this->post(route('kependudukan.warga.verify', ['nik_hash' => $this->wargaRt01->nik_hash]), $payload);

        $response->assertRedirect(route('kependudukan.warga.show', ['nik_hash' => $this->wargaRt01->nik_hash]));
        $response->assertSessionHas('error');
    }

    public function test_kartu_keluarga_web_views_and_create(): void
    {
        $this->actingAs($this->ketuaRt01);

        // Index KK
        $indexResponse = $this->get(route('kependudukan.kk.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee($this->kkRt01->no_kk_masked);
        $indexResponse->assertDontSee($this->kkRt02->no_kk_masked);

        // Create form
        $createFormResponse = $this->get(route('kependudukan.kk.create'));
        $createFormResponse->assertOk();

        // Store KK
        $payload = [
            'no_kk' => '3216010101019999',
            'rt_code' => '001',
            'alamat_lengkap' => 'Jl. Baru No. 99, RT 001',
            'blok' => 'C',
            'nomor_rumah' => '99',
            'status_kepemilikan_rumah' => 'MILIK SENDIRI',
        ];

        $storeResponse = $this->post(route('kependudukan.kk.store'), $payload);
        $storeResponse->assertRedirect(route('kependudukan.kk.index'));

        $this->assertDatabaseHas('kartu_keluargas', [
            'rt_code' => '001',
            'blok' => 'C',
            'nomor_rumah' => '99',
        ]);
    }
}
