<?php

declare(strict_types=1);

namespace Tests\Feature\LaporanAspirasi;

use App\Enums\RoleName;
use App\Enums\StatusLaporan;
use App\Models\KartuKeluarga;
use App\Models\LaporanAspirasi;
use App\Models\Role;
use App\Models\User;
use App\Models\Warga;
use App\Modules\LaporanAspirasi\Services\LaporanAspirasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Tests\TestCase;

class LaporanAspirasiServiceTest extends TestCase
{
    use RefreshDatabase;

    private LaporanAspirasiService $service;

    private User $ketuaRt;

    private User $sekretarisRw;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->service = app(LaporanAspirasiService::class);

        $roleRt = Role::where('name', RoleName::KETUA_RT->value)->firstOrFail();
        $roleSek = Role::where('name', RoleName::SEKRETARIS_RW->value)->firstOrFail();

        $this->ketuaRt = User::factory()->create([
            'role_id' => $roleRt->id,
            'status' => 'ACTIVE',
            'rt_code' => '001',
        ]);

        $this->sekretarisRw = User::factory()->create([
            'role_id' => $roleSek->id,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_submit_laporan_with_valid_data_creates_record_and_audit_log(): void
    {
        $payload = [
            'judul_laporan' => 'Lampu Jalan Padam di RT 01',
            'teks_keluhan' => 'Lampu jalan di tiang nomor 5 mati sejak kemarin malam, mohon segera diganti.',
            'lokasi_kejadian' => 'Jl. Kenanga Depan No. 10',
        ];

        $laporan = $this->service->submitLaporan($payload);

        $this->assertInstanceOf(LaporanAspirasi::class, $laporan);
        $this->assertStringStartsWith('LPR-', $laporan->ticket_number);
        $this->assertEquals('Lampu Jalan Padam di RT 01', $laporan->judul_laporan);
        $this->assertEquals(StatusLaporan::SUBMITTED, $laporan->current_status);
        $this->assertNotNull($laporan->submitted_at);
        $this->assertNull($laporan->warga_id);

        $this->assertDatabaseHas('laporan_aspirasis', [
            'aspirasi_id' => $laporan->aspirasi_id,
            'ticket_number' => $laporan->ticket_number,
            'judul_laporan' => 'Lampu Jalan Padam di RT 01',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Laporan Aspirasi',
            'action' => 'CREATE_LAPORAN_ASPIRASI',
            'entity_id' => (string) $laporan->aspirasi_id,
        ]);
    }

    public function test_submit_laporan_with_registered_nik_links_to_warga(): void
    {
        $kk = KartuKeluarga::create([
            'no_kk' => '3216000000000001',
            'rt_code' => '001',
            'alamat_lengkap' => 'Jl. Kenanga 1',
            'status_kepemilikan_rumah' => 'MILIK_SENDIRI',
        ]);

        $warga = Warga::create([
            'kartu_keluarga_id' => $kk->id,
            'nik' => '3216000000000002',
            'no_kk' => '3216000000000001',
            'nama_lengkap' => 'Ahmad Warga',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1990-01-01',
            'pekerjaan' => 'Karyawan',
            'status_hubungan_keluarga' => 'KEPALA KELUARGA',
            'status_warga' => 'TETAP',
            'verification_status' => 'TERVERIFIKASI',
        ]);

        $payload = [
            'judul_laporan' => 'Saluran Air Mampet',
            'teks_keluhan' => 'Saluran drainase di depan pos RT 01 tersumbat sampah saat hujan deras.',
            'lokasi_kejadian' => 'Depan Pos RT 01',
            'nik' => '3216000000000002',
        ];

        $laporan = $this->service->submitLaporan($payload);

        $this->assertEquals($warga->id, $laporan->warga_id);
    }

    public function test_track_by_ticket_returns_laporan(): void
    {
        $laporan = $this->service->submitLaporan([
            'judul_laporan' => 'Pohon Tumbang',
            'teks_keluhan' => 'Ada dahan pohon besar yang hampir roboh ke jalan utama.',
            'lokasi_kejadian' => 'Jl. Utama RW 047',
        ]);

        $found = $this->service->trackByTicket($laporan->ticket_number);

        $this->assertEquals($laporan->aspirasi_id, $found->aspirasi_id);
        $this->assertEquals('Pohon Tumbang', $found->judul_laporan);
    }

    public function test_track_by_invalid_ticket_throws_not_found(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->service->trackByTicket('LPR-99999999-99999');
    }

    public function test_list_laporan_with_status_filter(): void
    {
        $lap1 = $this->service->submitLaporan([
            'judul_laporan' => 'Laporan 1',
            'teks_keluhan' => 'Deskripsi keluhan nomor satu dengan panjang minimal.',
        ]);

        $lap2 = $this->service->submitLaporan([
            'judul_laporan' => 'Laporan 2',
            'teks_keluhan' => 'Deskripsi keluhan nomor dua dengan panjang minimal.',
        ]);

        $this->service->updateStatus($lap2, $this->ketuaRt, [
            'current_status' => StatusLaporan::IN_PROGRESS->value,
        ]);

        $submittedList = $this->service->listLaporan(['current_status' => StatusLaporan::SUBMITTED->value]);
        $inProgressList = $this->service->listLaporan(['current_status' => StatusLaporan::IN_PROGRESS->value]);

        $this->assertEquals(1, $submittedList->total());
        $this->assertEquals(1, $inProgressList->total());
    }

    public function test_update_status_valid_transition_to_in_progress(): void
    {
        $laporan = $this->service->submitLaporan([
            'judul_laporan' => 'Lampu Mati',
            'teks_keluhan' => 'Deskripsi keluhan nomor satu dengan panjang minimal.',
        ]);

        $updated = $this->service->updateStatus($laporan, $this->ketuaRt, [
            'current_status' => StatusLaporan::IN_PROGRESS->value,
            'catatan_tindak_lanjut' => 'Sedang dikoordinasikan dengan petugas PLN.',
        ]);

        $this->assertEquals(StatusLaporan::IN_PROGRESS, $updated->current_status);
        $this->assertEquals('Sedang dikoordinasikan dengan petugas PLN.', $updated->catatan_tindak_lanjut);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Laporan Aspirasi',
            'action' => 'STATUS_CHANGE_LAPORAN',
            'entity_id' => (string) $laporan->aspirasi_id,
        ]);
    }

    public function test_update_status_transition_to_resolved_sets_resolved_at(): void
    {
        $laporan = $this->service->submitLaporan([
            'judul_laporan' => 'Lampu Mati',
            'teks_keluhan' => 'Deskripsi keluhan nomor satu dengan panjang minimal.',
        ]);

        $inProgress = $this->service->updateStatus($laporan, $this->ketuaRt, [
            'current_status' => StatusLaporan::IN_PROGRESS->value,
        ]);

        $resolved = $this->service->updateStatus($inProgress, $this->sekretarisRw, [
            'current_status' => StatusLaporan::RESOLVED->value,
            'catatan_tindak_lanjut' => 'Lampu telah diganti dengan yang baru dan menyala normal.',
        ]);

        $this->assertEquals(StatusLaporan::RESOLVED, $resolved->current_status);
        $this->assertNotNull($resolved->resolved_at);
        $this->assertEquals('Lampu telah diganti dengan yang baru dan menyala normal.', $resolved->catatan_tindak_lanjut);
    }

    public function test_update_status_to_resolved_without_catatan_throws_exception(): void
    {
        $laporan = $this->service->submitLaporan([
            'judul_laporan' => 'Lampu Mati',
            'teks_keluhan' => 'Deskripsi keluhan nomor satu dengan panjang minimal.',
        ]);

        $inProgress = $this->service->updateStatus($laporan, $this->ketuaRt, [
            'current_status' => StatusLaporan::IN_PROGRESS->value,
        ]);

        $this->expectException(HttpResponseException::class);

        $this->service->updateStatus($inProgress, $this->sekretarisRw, [
            'current_status' => StatusLaporan::RESOLVED->value,
            'catatan_tindak_lanjut' => '',
        ]);
    }

    public function test_update_status_invalid_transition_throws_exception(): void
    {
        $laporan = $this->service->submitLaporan([
            'judul_laporan' => 'Lampu Mati',
            'teks_keluhan' => 'Deskripsi keluhan nomor satu dengan panjang minimal.',
        ]);

        $this->expectException(UnprocessableEntityHttpException::class);

        // SUBMITTED langsung lompat ke RESOLVED tanpa IN_PROGRESS
        $this->service->updateStatus($laporan, $this->ketuaRt, [
            'current_status' => StatusLaporan::RESOLVED->value,
            'catatan_tindak_lanjut' => 'Langsung beres',
        ]);
    }

    public function test_update_status_from_closed_report_throws_exception(): void
    {
        $laporan = $this->service->submitLaporan([
            'judul_laporan' => 'Lampu Mati',
            'teks_keluhan' => 'Deskripsi keluhan nomor satu dengan panjang minimal.',
        ]);

        $inProgress = $this->service->updateStatus($laporan, $this->ketuaRt, [
            'current_status' => StatusLaporan::IN_PROGRESS->value,
        ]);

        $resolved = $this->service->updateStatus($inProgress, $this->sekretarisRw, [
            'current_status' => StatusLaporan::RESOLVED->value,
            'catatan_tindak_lanjut' => 'Sudah beres',
        ]);

        $closed = $this->service->updateStatus($resolved, $this->sekretarisRw, [
            'current_status' => StatusLaporan::CLOSED->value,
        ]);

        $this->expectException(UnprocessableEntityHttpException::class);

        // Mencoba membuka kembali laporan yang sudah CLOSED
        $this->service->updateStatus($closed, $this->ketuaRt, [
            'current_status' => StatusLaporan::IN_PROGRESS->value,
        ]);
    }

    public function test_delete_laporan_soft_deletes_and_records_audit(): void
    {
        $laporan = $this->service->submitLaporan([
            'judul_laporan' => 'Laporan Dibatalkan',
            'teks_keluhan' => 'Deskripsi keluhan yang keliru dan ingin dihapus pengurus.',
        ]);

        $this->service->deleteLaporan($laporan, $this->sekretarisRw);

        $this->assertSoftDeleted('laporan_aspirasis', [
            'aspirasi_id' => $laporan->aspirasi_id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Laporan Aspirasi',
            'action' => 'DELETE_LAPORAN_ASPIRASI',
            'entity_id' => (string) $laporan->aspirasi_id,
        ]);
    }
}
