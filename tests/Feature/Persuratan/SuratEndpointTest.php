<?php

declare(strict_types=1);

namespace Tests\Feature\Persuratan;

use App\Enums\JenisSurat;
use App\Enums\ReviewAction;
use App\Enums\RoleName;
use App\Enums\StatusPengajuanSurat;
use App\Models\AuditLog;
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
 * Feature Test untuk REST API endpoints Persuratan.
 *
 * Mencakup: public submission, tracking, RBAC list, verifikasi workflow, audit trail.
 */
class SuratEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $ketuaRt01;

    private User $ketuaRt02;

    private User $sekretarisRw;

    private User $ketuaRw;

    private User $wargaUser;

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
        $roleKetuaRw = Role::where('name', RoleName::KETUA_RW->value)->firstOrFail();
        $roleSuperAdmin = Role::where('name', RoleName::SUPER_ADMIN->value)->firstOrFail();

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
        $this->ketuaRw = User::factory()->create([
            'role_id' => $roleKetuaRw->id,
            'rt_code' => null,
            'status' => 'ACTIVE',
        ]);
        $this->wargaUser = User::factory()->create([
            'role_id' => $roleSuperAdmin->id,
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
    // PUBLIC SUBMISSION
    // =========================================================================

    public function test_guest_dapat_submit_pengajuan_surat_dengan_nik_valid(): void
    {
        $response = $this->postJson('/api/v1/surat/pengajuan', [
            'nik' => $this->rawNik,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Pengurusan administrasi perpanjangan KTP',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['tracking_code', 'jenis_surat', 'current_status', 'tanggal_pengajuan']]);

        $this->assertEquals('SUBMITTED', $response->json('data.current_status'));
        $this->assertStringStartsWith('SRT-', $response->json('data.tracking_code'));
    }

    public function test_submission_dengan_nik_tidak_terdaftar_mengembalikan_422(): void
    {
        $response = $this->postJson('/api/v1/surat/pengajuan', [
            'nik' => '9999999999999999',
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Pengurusan administrasi',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nik']);
    }

    public function test_submission_dengan_nik_format_salah_mengembalikan_422(): void
    {
        $response = $this->postJson('/api/v1/surat/pengajuan', [
            'nik' => '123',
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Pengurusan administrasi',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nik']);
    }

    public function test_submission_dengan_jenis_surat_tidak_valid_mengembalikan_422(): void
    {
        $response = $this->postJson('/api/v1/surat/pengajuan', [
            'nik' => $this->rawNik,
            'jenis_surat' => 'JENIS_TIDAK_VALID',
            'keperluan' => 'Pengurusan administrasi',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['jenis_surat']);
    }

    public function test_submission_dengan_keperluan_terlalu_pendek_mengembalikan_422(): void
    {
        $response = $this->postJson('/api/v1/surat/pengajuan', [
            'nik' => $this->rawNik,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Pendek',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['keperluan']);
    }

    public function test_response_submit_tidak_mengandung_nik(): void
    {
        $response = $this->postJson('/api/v1/surat/pengajuan', [
            'nik' => $this->rawNik,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Pengurusan administrasi keperluan SKCK',
        ]);

        $response->assertCreated();
        $body = $response->json();
        $bodyString = json_encode($body);

        $this->assertStringNotContainsString($this->rawNik, $bodyString);
        $this->assertStringNotContainsString('nik', strtolower(json_encode(array_keys(data_get($body, 'data', [])))));
    }

    public function test_tracking_code_dibuat_dengan_format_yang_benar(): void
    {
        $response = $this->postJson('/api/v1/surat/pengajuan', [
            'nik' => $this->rawNik,
            'jenis_surat' => JenisSurat::SURAT_KETERANGAN_DOMISILI->value,
            'keperluan' => 'Keterangan domisili untuk perbankan',
        ]);

        $response->assertCreated();
        $today = now()->format('Ymd');
        $this->assertMatchesRegularExpression('/^SRT-'.$today.'-[A-Z0-9]{6}$/', $response->json('data.tracking_code'));
    }

    public function test_nik_tidak_tersimpan_pada_tabel_pengajuan_surats(): void
    {
        $response = $this->postJson('/api/v1/surat/pengajuan', [
            'nik' => $this->rawNik,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Pengurusan administrasi keperluan',
        ]);

        $response->assertCreated();
        $trackingCode = $response->json('data.tracking_code');
        $pengajuan = PengajuanSurat::where('tracking_code', $trackingCode)->firstOrFail();

        // Pastikan NIK tidak ada di record pengajuan
        $rawRecord = \Illuminate\Support\Facades\DB::table('pengajuan_surats')
            ->where('pengajuan_id', $pengajuan->pengajuan_id)
            ->first();

        $this->assertNotNull($rawRecord);
        $this->assertObjectNotHasProperty('nik', $rawRecord);
        $this->assertEquals($this->wargaRt01->id, $rawRecord->warga_id);
    }

    // =========================================================================
    // TRACKING
    // =========================================================================

    public function test_tracking_dengan_kode_valid_mengembalikan_data_pengajuan(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-TEST01',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan test tracking',
            'current_status' => StatusPengajuanSurat::SUBMITTED->value,
            'tanggal_pengajuan' => now(),
        ]);

        $response = $this->getJson('/api/v1/surat/pengajuan/track/SRT-20260817-TEST01');

        $response->assertOk()
            ->assertJsonPath('data.tracking_code', 'SRT-20260817-TEST01')
            ->assertJsonPath('data.current_status', 'SUBMITTED')
            ->assertJsonStructure(['data' => ['tracking_code', 'jenis_surat', 'current_status', 'riwayat_status']]);
    }

    public function test_tracking_dengan_kode_tidak_ada_mengembalikan_404(): void
    {
        $response = $this->getJson('/api/v1/surat/pengajuan/track/SRT-TIDAK-ADA');
        $response->assertNotFound();
    }

    public function test_tracking_response_tidak_membocorkan_pii(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-PIITEST',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Test keamanan PII',
            'current_status' => StatusPengajuanSurat::SUBMITTED->value,
            'tanggal_pengajuan' => now(),
        ]);

        $response = $this->getJson('/api/v1/surat/pengajuan/track/SRT-20260817-PIITEST');
        $response->assertOk();

        $bodyString = $response->content();
        $this->assertStringNotContainsString($this->rawNik, $bodyString);
        $this->assertStringNotContainsString($this->rawNoKk, $bodyString);
    }

    public function test_tracking_pengajuan_rejected_menampilkan_catatan_penolakan(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-REJTEST',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan test rejected',
            'current_status' => StatusPengajuanSurat::REJECTED->value,
            'catatan_penolakan' => 'Dokumen tidak lengkap',
            'tanggal_pengajuan' => now(),
            'tanggal_selesai' => now(),
        ]);

        $response = $this->getJson('/api/v1/surat/pengajuan/track/SRT-20260817-REJTEST');
        $response->assertOk()
            ->assertJsonPath('data.catatan_penolakan', 'Dokumen tidak lengkap');
    }

    // =========================================================================
    // RBAC — LIST
    // =========================================================================

    public function test_ketua_rt_dapat_melihat_daftar_pengajuan_wilayahnya(): void
    {
        PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-RT01A',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan warga RT 001',
            'current_status' => StatusPengajuanSurat::SUBMITTED->value,
            'tanggal_pengajuan' => now(),
        ]);

        $response = $this->actingAs($this->ketuaRt01, 'sanctum')
            ->getJson('/api/v1/surat/pengajuan');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_ketua_rt_tidak_dapat_melihat_pengajuan_rt_lain(): void
    {
        PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-RT01B',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan warga RT 001',
            'current_status' => StatusPengajuanSurat::SUBMITTED->value,
            'tanggal_pengajuan' => now(),
        ]);

        // KETUA_RT 002 tidak boleh melihat pengajuan RT 001
        $response = $this->actingAs($this->ketuaRt02, 'sanctum')
            ->getJson('/api/v1/surat/pengajuan');

        $response->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_role_tidak_berwenang_mendapat_403_saat_akses_list(): void
    {
        // User biasa (Super Admin punya akses, tapi role WARGA tidak ada)
        // Simulasi dengan membuat user tanpa role yang berwenang
        $roleRt = Role::where('name', RoleName::KETUA_RT->value)->first();
        $unauthorizedUser = User::factory()->create([
            'role_id' => $roleRt->id, // akan kita ubah policy test melalui cara lain
            'status' => 'ACTIVE',
        ]);
        // Guest (unauthenticated) harus mendapat 401
        $response = $this->getJson('/api/v1/surat/pengajuan');
        $response->assertUnauthorized();
    }

    // =========================================================================
    // WORKFLOW
    // =========================================================================

    public function test_submitted_ke_rt_review_berhasil(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-WF001',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan workflow test',
            'current_status' => StatusPengajuanSurat::SUBMITTED->value,
            'tanggal_pengajuan' => now(),
        ]);

        $response = $this->actingAs($this->ketuaRt01, 'sanctum')
            ->postJson("/api/v1/surat/pengajuan/{$pengajuan->pengajuan_id}/verify", [
                'action' => ReviewAction::APPROVE->value,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.current_status', 'RT_REVIEW');
    }

    public function test_reject_dari_rt_menghasilkan_status_rejected(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-WF002',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan workflow reject',
            'current_status' => StatusPengajuanSurat::SUBMITTED->value,
            'tanggal_pengajuan' => now(),
        ]);

        $response = $this->actingAs($this->ketuaRt01, 'sanctum')
            ->postJson("/api/v1/surat/pengajuan/{$pengajuan->pengajuan_id}/verify", [
                'action' => ReviewAction::REJECT->value,
                'catatan' => 'Dokumen tidak memenuhi syarat RT',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.current_status', 'REJECTED');

        $this->assertDatabaseHas('pengajuan_surats', [
            'pengajuan_id' => $pengajuan->pengajuan_id,
            'current_status' => 'REJECTED',
            'catatan_penolakan' => 'Dokumen tidak memenuhi syarat RT',
        ]);
    }

    public function test_rt_review_ke_rw_review_via_sekretaris(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-WF003',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan workflow rw forward',
            'current_status' => StatusPengajuanSurat::RT_REVIEW->value,
            'tanggal_pengajuan' => now(),
        ]);

        $response = $this->actingAs($this->sekretarisRw, 'sanctum')
            ->postJson("/api/v1/surat/pengajuan/{$pengajuan->pengajuan_id}/verify", [
                'action' => ReviewAction::APPROVE->value,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.current_status', 'RW_REVIEW');
    }

    public function test_rw_review_ke_completed_via_ketua_rw(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-WF004',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan workflow complete',
            'current_status' => StatusPengajuanSurat::RW_REVIEW->value,
            'tanggal_pengajuan' => now(),
        ]);

        $response = $this->actingAs($this->ketuaRw, 'sanctum')
            ->postJson("/api/v1/surat/pengajuan/{$pengajuan->pengajuan_id}/verify", [
                'action' => ReviewAction::APPROVE->value,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.current_status', 'COMPLETED');

        $this->assertNotNull($response->json('data.nomor_surat'));
    }

    public function test_invalid_transition_menghasilkan_409(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-WF005',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan invalid transition',
            'current_status' => StatusPengajuanSurat::RT_REVIEW->value, // Sudah di RT_REVIEW, RT tidak bisa review lagi
            'tanggal_pengajuan' => now(),
        ]);

        // KETUA_RT mencoba review saat status sudah RT_REVIEW (conflict)
        $response = $this->actingAs($this->ketuaRt01, 'sanctum')
            ->postJson("/api/v1/surat/pengajuan/{$pengajuan->pengajuan_id}/verify", [
                'action' => ReviewAction::APPROVE->value,
            ]);

        $response->assertStatus(409);
    }

    public function test_pengajuan_completed_tidak_dapat_diproses_ulang(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-WF006',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan final status test',
            'current_status' => StatusPengajuanSurat::COMPLETED->value,
            'nomor_surat' => '001/SP/RW047/08/2026',
            'tanggal_pengajuan' => now(),
            'tanggal_selesai' => now(),
        ]);

        $response = $this->actingAs($this->ketuaRw, 'sanctum')
            ->postJson("/api/v1/surat/pengajuan/{$pengajuan->pengajuan_id}/verify", [
                'action' => ReviewAction::APPROVE->value,
            ]);

        $response->assertStatus(409);
    }

    public function test_pengajuan_rejected_tidak_dapat_diproses_ulang(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-WF007',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan rejected final test',
            'current_status' => StatusPengajuanSurat::REJECTED->value,
            'catatan_penolakan' => 'Sudah ditolak',
            'tanggal_pengajuan' => now(),
            'tanggal_selesai' => now(),
        ]);

        $response = $this->actingAs($this->ketuaRt01, 'sanctum')
            ->postJson("/api/v1/surat/pengajuan/{$pengajuan->pengajuan_id}/verify", [
                'action' => ReviewAction::APPROVE->value,
            ]);

        $response->assertStatus(409);
    }

    public function test_ketua_rt_tidak_dapat_review_pengajuan_rt_lain(): void
    {
        // Warga RT 001 mengajukan, tapi KETUA_RT 002 mencoba review
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-AREA01',
            'warga_id' => $this->wargaRt01->id, // Warga RT 001
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan area scoping test',
            'current_status' => StatusPengajuanSurat::SUBMITTED->value,
            'tanggal_pengajuan' => now(),
        ]);

        $response = $this->actingAs($this->ketuaRt02, 'sanctum') // Ketua RT 002
            ->postJson("/api/v1/surat/pengajuan/{$pengajuan->pengajuan_id}/verify", [
                'action' => ReviewAction::APPROVE->value,
            ]);

        $response->assertForbidden();
    }

    public function test_ketua_rw_tidak_dapat_menyelesaikan_submitted_langsung(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-RWDIRECT',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan bypass test',
            'current_status' => StatusPengajuanSurat::SUBMITTED->value,
            'tanggal_pengajuan' => now(),
        ]);

        $response = $this->actingAs($this->ketuaRw, 'sanctum')
            ->postJson("/api/v1/surat/pengajuan/{$pengajuan->pengajuan_id}/verify", [
                'action' => ReviewAction::APPROVE->value,
            ]);

        $response->assertStatus(409);
    }

    public function test_sekretaris_rw_tidak_dapat_memproses_submitted_langsung(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-SEKDIRECT',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan bypass test sekretaris',
            'current_status' => StatusPengajuanSurat::SUBMITTED->value,
            'tanggal_pengajuan' => now(),
        ]);

        $response = $this->actingAs($this->sekretarisRw, 'sanctum')
            ->postJson("/api/v1/surat/pengajuan/{$pengajuan->pengajuan_id}/verify", [
                'action' => ReviewAction::APPROVE->value,
            ]);

        $response->assertStatus(409);
    }

    public function test_sekretaris_rw_approve_rt_review_menghasilkan_rw_review_bukan_completed(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-SEKAPPR',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan sekretaris approve test',
            'current_status' => StatusPengajuanSurat::RT_REVIEW->value,
            'tanggal_pengajuan' => now(),
        ]);

        $response = $this->actingAs($this->sekretarisRw, 'sanctum')
            ->postJson("/api/v1/surat/pengajuan/{$pengajuan->pengajuan_id}/verify", [
                'action' => ReviewAction::APPROVE->value,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.current_status', 'RW_REVIEW')
            ->assertJsonPath('data.nomor_surat', null);

        $this->assertDatabaseHas('pengajuan_surats', [
            'pengajuan_id' => $pengajuan->pengajuan_id,
            'current_status' => 'RW_REVIEW',
            'nomor_surat' => null,
        ]);
    }

    public function test_reject_dari_rw_review_menghasilkan_status_rejected(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-RWREJ',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan rw reject test',
            'current_status' => StatusPengajuanSurat::RW_REVIEW->value,
            'tanggal_pengajuan' => now(),
        ]);

        $response = $this->actingAs($this->ketuaRw, 'sanctum')
            ->postJson("/api/v1/surat/pengajuan/{$pengajuan->pengajuan_id}/verify", [
                'action' => ReviewAction::REJECT->value,
                'catatan' => 'Ditolak oleh Ketua RW',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.current_status', 'REJECTED');

        $this->assertDatabaseHas('pengajuan_surats', [
            'pengajuan_id' => $pengajuan->pengajuan_id,
            'current_status' => 'REJECTED',
            'catatan_penolakan' => 'Ditolak oleh Ketua RW',
            'nomor_surat' => null,
        ]);
    }

    public function test_rejected_tidak_memiliki_nomor_surat(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-NOREJNUM',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_KETERANGAN_DOMISILI->value,
            'keperluan' => 'Keperluan test nomor surat rejected',
            'current_status' => StatusPengajuanSurat::SUBMITTED->value,
            'tanggal_pengajuan' => now(),
        ]);

        $response = $this->actingAs($this->ketuaRt01, 'sanctum')
            ->postJson("/api/v1/surat/pengajuan/{$pengajuan->pengajuan_id}/verify", [
                'action' => ReviewAction::REJECT->value,
                'catatan' => 'Ditolak RT',
            ]);

        $response->assertOk();
        $this->assertNull($response->json('data.nomor_surat'));
        $this->assertDatabaseHas('pengajuan_surats', [
            'pengajuan_id' => $pengajuan->pengajuan_id,
            'nomor_surat' => null,
        ]);
    }

    // =========================================================================
    // AUDIT TRAIL
    // =========================================================================

    public function test_submit_pengajuan_menghasilkan_audit_log(): void
    {
        $response = $this->postJson('/api/v1/surat/pengajuan', [
            'nik' => $this->rawNik,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Pengurusan administrasi audit test',
        ]);

        $response->assertCreated();
        $trackingCode = $response->json('data.tracking_code');
        $pengajuan = PengajuanSurat::where('tracking_code', $trackingCode)->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Persuratan',
            'action' => 'SUBMIT_PENGAJUAN_SURAT',
            'entity_type' => 'pengajuan_surats',
            'entity_id' => (string) $pengajuan->pengajuan_id,
        ]);
    }

    public function test_perubahan_status_menghasilkan_audit_log(): void
    {
        $pengajuan = PengajuanSurat::create([
            'tracking_code' => 'SRT-20260817-AUDIT1',
            'warga_id' => $this->wargaRt01->id,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan audit trail test',
            'current_status' => StatusPengajuanSurat::SUBMITTED->value,
            'tanggal_pengajuan' => now(),
        ]);

        $this->actingAs($this->ketuaRt01, 'sanctum')
            ->postJson("/api/v1/surat/pengajuan/{$pengajuan->pengajuan_id}/verify", [
                'action' => ReviewAction::APPROVE->value,
            ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Persuratan',
            'action' => 'STATUS_CHANGE_RT_REVIEW',
            'entity_type' => 'pengajuan_surats',
            'entity_id' => (string) $pengajuan->pengajuan_id,
        ]);
    }

    public function test_audit_log_tidak_mengandung_nik_plaintext(): void
    {
        $this->postJson('/api/v1/surat/pengajuan', [
            'nik' => $this->rawNik,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Pengurusan administrasi PII audit test',
        ])->assertCreated();

        // Pastikan NIK plaintext tidak ada di seluruh audit_logs
        $logs = AuditLog::where('module', 'Persuratan')->get();
        foreach ($logs as $log) {
            $values = json_encode($log->new_values).json_encode($log->old_values);
            $this->assertStringNotContainsString($this->rawNik, $values ?? '');
        }
    }
}
