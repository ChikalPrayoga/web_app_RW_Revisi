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
use App\Modules\Persuratan\Requests\StorePengajuanSuratRequest;
use App\Modules\Persuratan\Services\SuratService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class SuratFoundationTest extends TestCase
{
    use RefreshDatabase;

    private SuratService $suratService;

    private User $ketuaRt01;

    private User $ketuaRt02;

    private User $sekretarisRw;

    private User $ketuaRw;

    private KartuKeluarga $kkRt01;

    private Warga $wargaRt01;

    private string $rawNik = '3216011505900021';

    private string $rawNoKk = '3216010101230012';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->suratService = app(SuratService::class);

        $roleRt = Role::where('name', RoleName::KETUA_RT->value)->firstOrFail();
        $roleSekretaris = Role::where('name', RoleName::SEKRETARIS_RW->value)->firstOrFail();
        $roleKetuaRw = Role::where('name', RoleName::KETUA_RW->value)->firstOrFail();

        $this->ketuaRt01 = User::factory()->create([
            'role_id' => $roleRt->id,
            'email' => 'rt01_surat@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => '001',
            'status' => 'ACTIVE',
        ]);

        $this->ketuaRt02 = User::factory()->create([
            'role_id' => $roleRt->id,
            'email' => 'rt02_surat@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => '002',
            'status' => 'ACTIVE',
        ]);

        $this->sekretarisRw = User::factory()->create([
            'role_id' => $roleSekretaris->id,
            'email' => 'sekretaris_surat@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => null,
            'status' => 'ACTIVE',
        ]);

        $this->ketuaRw = User::factory()->create([
            'role_id' => $roleKetuaRw->id,
            'email' => 'ketuarw_surat@rw047.id',
            'password' => Hash::make('Password123!'),
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

    /**
     * Test 1 & 2: Public submission dengan NIK valid tanpa autentikasi (guest).
     */
    public function test_public_submission_with_valid_nik_creates_pengajuan(): void
    {
        $payload = [
            'nik' => $this->rawNik,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Pengurusan administrasi perpanjangan KTP',
        ];

        $pengajuan = $this->suratService->submitPengajuan($payload);

        $this->assertInstanceOf(PengajuanSurat::class, $pengajuan);
        $this->assertNotNull($pengajuan->pengajuan_id);
        $this->assertEquals($this->wargaRt01->id, $pengajuan->warga_id);
        $this->assertEquals(StatusPengajuanSurat::SUBMITTED, $pengajuan->current_status);
        $this->assertEquals(JenisSurat::SURAT_PENGANTAR, $pengajuan->jenis_surat);
        $this->assertEquals('Pengurusan administrasi perpanjangan KTP', $pengajuan->keperluan);
        $this->assertNotNull($pengajuan->tracking_code);
        $this->assertStringStartsWith('SRT-', $pengajuan->tracking_code);
        $this->assertNull($pengajuan->catatan_penolakan);
        $this->assertNull($pengajuan->nomor_surat);
    }

    /**
     * Test 3: NIK tidak terdaftar → ditolak dengan ValidationException.
     */
    public function test_submission_with_unregistered_nik_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $payload = [
            'nik' => '9999999999999999',
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Pengurusan SKCK',
        ];

        $this->suratService->submitPengajuan($payload);
    }

    /**
     * Test 4: Tracking code dibuat unik dan sesuai format.
     */
    public function test_tracking_code_is_generated_with_expected_format(): void
    {
        $pengajuan = $this->suratService->submitPengajuan([
            'nik' => $this->rawNik,
            'jenis_surat' => JenisSurat::SURAT_KETERANGAN_DOMISILI->value,
            'keperluan' => 'Keterangan domisili untuk perbankan',
        ]);

        $today = now()->format('Ymd');
        $this->assertMatchesRegularExpression('/^SRT-'.$today.'-[A-Z0-9]{6}$/', $pengajuan->tracking_code);
    }

    /**
     * Test 5 & 6: warga_id tersimpan dan status awal SUBMITTED.
     */
    public function test_warga_id_is_persisted_and_initial_status_is_submitted(): void
    {
        $pengajuan = $this->suratService->submitPengajuan([
            'nik' => $this->rawNik,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Pengurusan administrasi surat pengantar',
        ]);

        $this->assertDatabaseHas('pengajuan_surats', [
            'pengajuan_id' => $pengajuan->pengajuan_id,
            'warga_id' => $this->wargaRt01->id,
            'current_status' => 'SUBMITTED',
            'tracking_code' => $pengajuan->tracking_code,
        ]);
    }

    /**
     * Test 7: Plaintext NIK tidak disimpan langsung pada tabel pengajuan_surats.
     */
    public function test_plaintext_nik_is_not_stored_in_pengajuan_surats_table(): void
    {
        $pengajuan = $this->suratService->submitPengajuan([
            'nik' => $this->rawNik,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Pengurusan surat pengantar',
        ]);

        $rawRecord = DB::table('pengajuan_surats')
            ->where('pengajuan_id', $pengajuan->pengajuan_id)
            ->first();

        $this->assertNotNull($rawRecord);
        $this->assertObjectNotHasProperty('nik', $rawRecord);
        $this->assertObjectNotHasProperty('nik_pemohon', $rawRecord);
        $this->assertObjectNotHasProperty('nik_pemohon_hash', $rawRecord);
        $this->assertEquals($this->wargaRt01->id, $rawRecord->warga_id);
    }

    /**
     * Test 8: RT review rejection menyimpan catatan_penolakan dan status REJECTED.
     */
    public function test_rt_review_rejection_stores_catatan_penolakan(): void
    {
        $pengajuan = $this->suratService->submitPengajuan([
            'nik' => $this->rawNik,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Pengurusan surat pengantar',
        ]);

        $rejectionNotes = 'Dokumen pengantar RT belum lengkap, silakan temui Ketua RT';
        $updated = $this->suratService->reviewByRt($this->ketuaRt01, $pengajuan, [
            'action' => ReviewAction::REJECT->value,
            'catatan' => $rejectionNotes,
        ]);

        $this->assertEquals(StatusPengajuanSurat::REJECTED, $updated->current_status);
        $this->assertEquals($rejectionNotes, $updated->catatan_penolakan);
        $this->assertNotNull($updated->tanggal_selesai);

        $this->assertDatabaseHas('pengajuan_surats', [
            'pengajuan_id' => $pengajuan->pengajuan_id,
            'current_status' => 'REJECTED',
            'catatan_penolakan' => $rejectionNotes,
        ]);
    }

    /**
     * Test 9: RW verification rejection menyimpan catatan_penolakan.
     */
    public function test_rw_verify_rejection_stores_catatan_penolakan(): void
    {
        $pengajuan = $this->suratService->submitPengajuan([
            'nik' => $this->rawNik,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Pengurusan surat pengantar',
        ]);

        // RT approves first
        $this->suratService->reviewByRt($this->ketuaRt01, $pengajuan, [
            'action' => ReviewAction::APPROVE->value,
        ]);

        // RW rejects
        $rejectionNotes = 'Format permohonan tidak sesuai ketentuan RW 047';
        $rejectedByRw = $this->suratService->verifyByRw($this->sekretarisRw, $pengajuan->fresh(), [
            'action' => ReviewAction::REJECT->value,
            'catatan' => $rejectionNotes,
        ]);

        $this->assertEquals(StatusPengajuanSurat::REJECTED, $rejectedByRw->current_status);
        $this->assertEquals($rejectionNotes, $rejectedByRw->catatan_penolakan);
        $this->assertNotNull($rejectedByRw->tanggal_selesai);
    }

    /**
     * Test 10: tracking dapat membaca status dan catatan_penolakan saat REJECTED.
     */
    public function test_track_by_kode_returns_catatan_penolakan_when_rejected(): void
    {
        $pengajuan = $this->suratService->submitPengajuan([
            'nik' => $this->rawNik,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Pengurusan surat pengantar',
        ]);

        $rejectionNotes = 'Data tidak lengkap';
        $this->suratService->reviewByRt($this->ketuaRt01, $pengajuan, [
            'action' => ReviewAction::REJECT->value,
            'catatan' => $rejectionNotes,
        ]);

        $tracked = $this->suratService->trackByKode($pengajuan->tracking_code);

        $this->assertEquals($pengajuan->tracking_code, $tracked->tracking_code);
        $this->assertEquals(StatusPengajuanSurat::REJECTED, $tracked->current_status);
        $this->assertEquals($rejectionNotes, $tracked->catatan_penolakan);
    }

    /**
     * Test: trackByKode yang tidak ada melempar NotFoundHttpException.
     */
    public function test_track_by_kode_non_existent_throws_not_found(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->suratService->trackByKode('SRT-99999999-NONEXIST');
    }

    /**
     * Test: Area scoping list pengajuan untuk Ketua RT.
     */
    public function test_list_pengajuan_is_area_scoped_for_ketua_rt(): void
    {
        // Pengajuan di RT 001
        $this->suratService->submitPengajuan([
            'nik' => $this->rawNik,
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan RT 001',
        ]);

        // Ketua RT 001 melihat 1 pengajuan
        $listRt01 = $this->suratService->listPengajuan($this->ketuaRt01, []);
        $this->assertEquals(1, $listRt01->total());

        // Ketua RT 002 melihat 0 pengajuan (area-scoped)
        $listRt02 = $this->suratService->listPengajuan($this->ketuaRt02, []);
        $this->assertEquals(0, $listRt02->total());

        // Sekretaris RW melihat seluruh wilayah (1 pengajuan)
        $listRw = $this->suratService->listPengajuan($this->sekretarisRw, []);
        $this->assertEquals(1, $listRw->total());
    }

    /**
     * Test: Form request StorePengajuanSuratRequest validation rules.
     */
    public function test_store_pengajuan_surat_request_validation(): void
    {
        $request = new StorePengajuanSuratRequest;
        $rules = $request->rules();

        // Valid data
        $validator = Validator::make([
            'nik' => '3216011505900021',
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan administrasi kependudukan',
        ], $rules);
        $this->assertTrue($validator->passes());

        // Invalid NIK (not 16 digits)
        $validatorInvalidNik = Validator::make([
            'nik' => '123',
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Keperluan administrasi kependudukan',
        ], $rules);
        $this->assertFalse($validatorInvalidNik->passes());
        $this->assertArrayHasKey('nik', $validatorInvalidNik->errors()->toArray());

        // Invalid jenis_surat
        $validatorInvalidJenis = Validator::make([
            'nik' => '3216011505900021',
            'jenis_surat' => 'INVALID_JENIS',
            'keperluan' => 'Keperluan administrasi kependudukan',
        ], $rules);
        $this->assertFalse($validatorInvalidJenis->passes());

        // Short keperluan (< 10 chars)
        $validatorShortKeperluan = Validator::make([
            'nik' => '3216011505900021',
            'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
            'keperluan' => 'Pendek',
        ], $rules);
        $this->assertFalse($validatorShortKeperluan->passes());
    }
}
