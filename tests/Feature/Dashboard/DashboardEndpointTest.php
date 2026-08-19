<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Enums\JenisSurat;
use App\Enums\RoleName;
use App\Enums\StatusCatatanIuran;
use App\Enums\StatusLaporan;
use App\Enums\StatusPengajuanSurat;
use App\Models\CatatanIuran;
use App\Models\IuranType;
use App\Models\KartuKeluarga;
use App\Models\LaporanAspirasi;
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
 * Feature Test untuk endpoint API Dashboard (GET /api/v1/dashboard/summary).
 *
 * @see API_SPECIFICATION.md §3.8.1
 * @see USER_STORIES.md US-DASH-01
 */
class DashboardEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $ketuaRt01;

    private User $ketuaRt02;

    private User $sekretarisRw;

    private User $bendaharaRw;

    private User $ketuaRw;

    private User $superAdmin;

    private User $wargaUser;

    private KartuKeluarga $kkRt01;

    private KartuKeluarga $kkRt02;

    private Warga $wargaRt01;

    private Warga $wargaRt02;

    private IuranType $iuranTypeIkk;

    private string $rawNoKk01 = '3216010101230001';

    private string $rawNik01 = '3216011505900001';

    private string $rawNoKk02 = '3216010101230002';

    private string $rawNik02 = '3216011505900002';

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
            'status' => 'ACTIVE',
        ]);

        $this->bendaharaRw = User::factory()->create([
            'role_id' => $roleBendahara->id,
            'status' => 'ACTIVE',
        ]);

        $this->ketuaRw = User::factory()->create([
            'role_id' => $roleKetuaRw->id,
            'status' => 'ACTIVE',
        ]);

        $this->superAdmin = User::factory()->create([
            'role_id' => $roleSuperAdmin->id,
            'status' => 'ACTIVE',
        ]);

        $this->wargaUser = User::factory()->create([
            'role_id' => $roleWarga->id,
            'status' => 'ACTIVE',
        ]);

        // Setup Data Kependudukan RT 001 & RT 002
        $this->kkRt01 = KartuKeluarga::create([
            'no_kk' => $this->rawNoKk01,
            'rt_code' => '001',
            'alamat_lengkap' => 'Jl. Mawar No. 1',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ]);

        $this->wargaRt01 = Warga::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'nik' => $this->rawNik01,
            'no_kk' => $this->rawNoKk01,
            'nama_lengkap' => 'Budi Santoso RT01',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1990-05-15',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
            'status_warga' => 'TETAP',
            'verification_status' => 'TERVERIFIKASI',
        ]);

        $this->kkRt02 = KartuKeluarga::create([
            'no_kk' => $this->rawNoKk02,
            'rt_code' => '002',
            'alamat_lengkap' => 'Jl. Melati No. 2',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ]);

        $this->wargaRt02 = Warga::create([
            'kartu_keluarga_id' => $this->kkRt02->id,
            'nik' => $this->rawNik02,
            'no_kk' => $this->rawNoKk02,
            'nama_lengkap' => 'Siti Rahma RT02',
            'jenis_kelamin' => 'P',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '1992-08-20',
            'status_hubungan_keluarga' => 'Istri',
            'status_warga' => 'TETAP',
            'verification_status' => 'TERVERIFIKASI',
        ]);

        $this->iuranTypeIkk = IuranType::where('code', 'IKK')->firstOrFail();
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/dashboard/summary');

        $response->assertStatus(401);
    }

    public function test_warga_role_returns_403_forbidden(): void
    {
        Sanctum::actingAs($this->wargaUser);

        $response = $this->getJson('/api/v1/dashboard/summary');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akses ditolak. Peran Anda tidak memiliki izin untuk melihat data dashboard.',
            ]);
    }

    public function test_ketua_rt_receives_area_scoped_summary(): void
    {
        // Pengajuan surat RT 001 (SUBMITTED) & RT 002 (SUBMITTED)
        PengajuanSurat::create([
            'tracking_code' => 'SRT-20260818-AAAA01',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Urus KTP RT 001',
            'current_status' => StatusPengajuanSurat::SUBMITTED->value,
            'tanggal_pengajuan' => now(),
        ]);

        PengajuanSurat::create([
            'tracking_code' => 'SRT-20260818-BBBB02',
            'warga_id' => $this->wargaRt02->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Urus KTP RT 002',
            'current_status' => StatusPengajuanSurat::SUBMITTED->value,
            'tanggal_pengajuan' => now(),
        ]);

        // Catatan Iuran RT 001 APPROVED & RT 002 APPROVED bulan ini
        $currMonth = (int) now()->format('n');
        $currYear = (int) now()->format('Y');

        CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'periode_bulan' => $currMonth,
            'periode_tahun' => $currYear,
            'nominal' => 50000.00,
            'status' => StatusCatatanIuran::APPROVED->value,
            'recorded_by_user_id' => $this->ketuaRt01->id,
            'approved_by_user_id' => $this->bendaharaRw->id,
        ]);

        CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt02->id,
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'periode_bulan' => $currMonth,
            'periode_tahun' => $currYear,
            'nominal' => 75000.00,
            'status' => StatusCatatanIuran::APPROVED->value,
            'recorded_by_user_id' => $this->ketuaRt02->id,
            'approved_by_user_id' => $this->bendaharaRw->id,
        ]);

        // Laporan
        LaporanAspirasi::create([
            'ticket_number' => 'LPR-20260818-00001',
            'judul_laporan' => 'Lampu Jalan Mati',
            'teks_keluhan' => 'Lampu jalan mati di perempatan',
            'current_status' => StatusLaporan::SUBMITTED->value,
            'submitted_at' => now(),
        ]);

        Sanctum::actingAs($this->ketuaRt01);

        $response = $this->getJson('/api/v1/dashboard/summary');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Data dashboard berhasil diambil',
                'data' => [
                    'total_warga' => 1, // Hanya RT 001
                    'total_kk' => 1,    // Hanya RT 001
                    'surat_menunggu_verifikasi' => 1, // Hanya RT 001
                    'laporan_aktif' => 1,
                    'total_iuran_bulan_ini' => 50000.00, // Hanya RT 001
                    'kepatuhan_iuran_persen' => 100.0,
                ],
            ]);
    }

    public function test_sekretaris_rw_receives_rw_wide_summary(): void
    {
        // 1 surat RT_REVIEW, 1 surat SUBMITTED
        PengajuanSurat::create([
            'tracking_code' => 'SRT-20260818-RRRR01',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Surat Pengantar RT_REVIEW',
            'current_status' => StatusPengajuanSurat::RT_REVIEW->value,
            'tanggal_pengajuan' => now(),
        ]);

        PengajuanSurat::create([
            'tracking_code' => 'SRT-20260818-SSSS02',
            'warga_id' => $this->wargaRt02->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Surat Pengantar SUBMITTED',
            'current_status' => StatusPengajuanSurat::SUBMITTED->value,
            'tanggal_pengajuan' => now(),
        ]);

        Sanctum::actingAs($this->sekretarisRw);

        $response = $this->getJson('/api/v1/dashboard/summary');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_warga' => 2, // Seluruh RW
                    'total_kk' => 2,    // Seluruh RW
                    'surat_menunggu_verifikasi' => 1, // RT_REVIEW
                ],
            ]);
    }

    public function test_ketua_rw_receives_rw_wide_summary(): void
    {
        PengajuanSurat::create([
            'tracking_code' => 'SRT-20260818-RW0001',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Surat Pengantar RW_REVIEW',
            'current_status' => StatusPengajuanSurat::RW_REVIEW->value,
            'tanggal_pengajuan' => now(),
        ]);

        Sanctum::actingAs($this->ketuaRw);

        $response = $this->getJson('/api/v1/dashboard/summary');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_warga' => 2,
                    'total_kk' => 2,
                    'surat_menunggu_verifikasi' => 1,
                ],
            ]);
    }

    public function test_bendahara_rw_receives_rw_wide_financial_summary(): void
    {
        $currMonth = (int) now()->format('n');
        $currYear = (int) now()->format('Y');

        CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'periode_bulan' => $currMonth,
            'periode_tahun' => $currYear,
            'nominal' => 50000.00,
            'status' => StatusCatatanIuran::APPROVED->value,
            'recorded_by_user_id' => $this->ketuaRt01->id,
            'approved_by_user_id' => $this->bendaharaRw->id,
        ]);

        CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt02->id,
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'periode_bulan' => $currMonth,
            'periode_tahun' => $currYear,
            'nominal' => 50000.00,
            'status' => StatusCatatanIuran::APPROVED->value,
            'recorded_by_user_id' => $this->ketuaRt02->id,
            'approved_by_user_id' => $this->bendaharaRw->id,
        ]);

        Sanctum::actingAs($this->bendaharaRw);

        $response = $this->getJson('/api/v1/dashboard/summary');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_warga' => 2,
                    'total_kk' => 2,
                    'total_iuran_bulan_ini' => 100000.00, // Total RW
                    'kepatuhan_iuran_persen' => 100.0,
                ],
            ]);
    }

    public function test_super_admin_receives_summary(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->getJson('/api/v1/dashboard/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_warga',
                    'total_kk',
                    'surat_menunggu_verifikasi',
                    'laporan_aktif',
                    'laporan_berdasarkan_status' => [
                        'SUBMITTED',
                        'IN_PROGRESS',
                        'RESOLVED',
                    ],
                    'total_iuran_bulan_ini',
                    'kepatuhan_iuran_persen',
                ],
            ]);
    }
}
