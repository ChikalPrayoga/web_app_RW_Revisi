<?php

declare(strict_types=1);

namespace Tests\Feature\LaporanAspirasi;

use App\Enums\RoleName;
use App\Enums\StatusLaporan;
use App\Models\AuditLog;
use App\Models\KartuKeluarga;
use App\Models\LaporanAspirasi;
use App\Models\Role;
use App\Models\User;
use App\Models\Warga;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LaporanAspirasiSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $ketuaRt;

    private User $bendaharaRw;

    private User $sekretarisRw;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $roleRt = Role::where('name', RoleName::KETUA_RT->value)->firstOrFail();
        $roleBendahara = Role::where('name', RoleName::BENDAHARA_RW->value)->firstOrFail();
        $roleSekretaris = Role::where('name', RoleName::SEKRETARIS_RW->value)->firstOrFail();

        $this->ketuaRt = User::factory()->create([
            'role_id' => $roleRt->id,
            'status' => 'ACTIVE',
            'rt_code' => '001',
        ]);

        $this->bendaharaRw = User::factory()->create([
            'role_id' => $roleBendahara->id,
            'status' => 'ACTIVE',
        ]);

        $this->sekretarisRw = User::factory()->create([
            'role_id' => $roleSekretaris->id,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_public_tracking_does_not_expose_pii_or_warga_id(): void
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
            'nama_lengkap' => 'Budi Rahasia',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1990-01-01',
            'pekerjaan' => 'Karyawan',
            'status_hubungan_keluarga' => 'KEPALA KELUARGA',
            'status_warga' => 'TETAP',
            'verification_status' => 'TERVERIFIKASI',
        ]);

        $laporan = LaporanAspirasi::create([
            'ticket_number' => 'LPR-20260818-00001',
            'warga_id' => $warga->id,
            'judul_laporan' => 'Pohon Tumbang',
            'teks_keluhan' => 'Dahan pohon tumbang di dekat kabel listrik tegangan tinggi.',
            'lokasi_kejadian' => 'Jl. Kenanga Depan No. 1',
            'current_status' => StatusLaporan::SUBMITTED,
            'submitted_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/laporan-aspirasi/track/{$laporan->ticket_number}");

        $response->assertStatus(200);
        $json = $response->json('data');

        // Pastikan tidak ada data sensitif / ID internal bocor ke response publik
        $this->assertArrayNotHasKey('warga_id', $json);
        $this->assertArrayNotHasKey('nik', $json);
        $this->assertArrayNotHasKey('no_kk', $json);
        $this->assertArrayNotHasKey('nama_lengkap', $json);
    }

    public function test_bendahara_cannot_mutate_or_update_laporan_status(): void
    {
        $laporan = LaporanAspirasi::create([
            'ticket_number' => 'LPR-20260818-00001',
            'judul_laporan' => 'Lampu Mati',
            'teks_keluhan' => 'Deskripsi keluhan nomor satu dengan panjang minimal.',
            'current_status' => StatusLaporan::SUBMITTED,
            'submitted_at' => now(),
        ]);

        Sanctum::actingAs($this->bendaharaRw);

        $response = $this->patchJson("/api/v1/laporan-aspirasi/{$laporan->aspirasi_id}/status", [
            'current_status' => 'IN_PROGRESS',
        ]);

        $response->assertStatus(403);
    }

    public function test_closed_report_cannot_be_updated_by_any_role(): void
    {
        $laporan = LaporanAspirasi::create([
            'ticket_number' => 'LPR-20260818-00001',
            'judul_laporan' => 'Lampu Mati',
            'teks_keluhan' => 'Deskripsi keluhan nomor satu dengan panjang minimal.',
            'current_status' => StatusLaporan::CLOSED,
            'submitted_at' => now(),
        ]);

        Sanctum::actingAs($this->sekretarisRw);

        $response = $this->patchJson("/api/v1/laporan-aspirasi/{$laporan->aspirasi_id}/status", [
            'current_status' => 'IN_PROGRESS',
        ]);

        $response->assertStatus(403);
    }

    public function test_audit_log_created_for_laporan_lifecycle_without_pii(): void
    {
        $payload = [
            'judul_laporan' => 'Pohon Tumbang',
            'teks_keluhan' => 'Dahan pohon tumbang di dekat kabel listrik tegangan tinggi.',
            'lokasi_kejadian' => 'Jl. Kenanga Depan No. 1',
        ];

        $response = $this->postJson('/api/v1/laporan-aspirasi', $payload);
        $response->assertStatus(201);

        $auditLog = AuditLog::where('module', 'Laporan Aspirasi')
            ->where('action', 'CREATE_LAPORAN_ASPIRASI')
            ->latest('id')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertArrayNotHasKey('nik', $auditLog->new_values ?? []);
        $this->assertArrayNotHasKey('password', $auditLog->new_values ?? []);
    }

    public function test_guest_cannot_update_laporan_status_via_api(): void
    {
        $laporan = LaporanAspirasi::create([
            'ticket_number' => 'LPR-20260818-00001',
            'judul_laporan' => 'Lampu Mati',
            'teks_keluhan' => 'Deskripsi keluhan nomor satu dengan panjang minimal.',
            'current_status' => StatusLaporan::SUBMITTED,
            'submitted_at' => now(),
        ]);

        $response = $this->patchJson("/api/v1/laporan-aspirasi/{$laporan->aspirasi_id}/status", [
            'current_status' => 'IN_PROGRESS',
        ]);

        $response->assertStatus(401);
    }

    public function test_warga_role_cannot_update_laporan_status_via_api(): void
    {
        $roleWarga = Role::where('name', RoleName::WARGA->value)->firstOrFail();
        $wargaUser = User::factory()->create([
            'role_id' => $roleWarga->id,
            'status' => 'ACTIVE',
        ]);

        $laporan = LaporanAspirasi::create([
            'ticket_number' => 'LPR-20260818-00001',
            'judul_laporan' => 'Lampu Mati',
            'teks_keluhan' => 'Deskripsi keluhan nomor satu dengan panjang minimal.',
            'current_status' => StatusLaporan::SUBMITTED,
            'submitted_at' => now(),
        ]);

        Sanctum::actingAs($wargaUser);

        $response = $this->patchJson("/api/v1/laporan-aspirasi/{$laporan->aspirasi_id}/status", [
            'current_status' => 'IN_PROGRESS',
        ]);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_delete_laporan_via_api(): void
    {
        $laporan = LaporanAspirasi::create([
            'ticket_number' => 'LPR-20260818-00001',
            'judul_laporan' => 'Lampu Mati',
            'teks_keluhan' => 'Deskripsi keluhan nomor satu dengan panjang minimal.',
            'current_status' => StatusLaporan::SUBMITTED,
            'submitted_at' => now(),
        ]);

        $response = $this->deleteJson("/api/v1/laporan-aspirasi/{$laporan->aspirasi_id}");

        $response->assertStatus(401);
    }

    public function test_xss_html_tags_are_stripped_on_submission(): void
    {
        $payload = [
            'judul_laporan' => '<script>alert("XSS")</script>Lampu Jalan Rusak',
            'teks_keluhan' => '<b>Penerangan</b> jalan mati sejak semalam <img src=x onerror=alert(1)> segera perbaiki.',
            'lokasi_kejadian' => '<i>Jl. Mawar 5</i>',
        ];

        $response = $this->postJson('/api/v1/laporan-aspirasi', $payload);
        $response->assertStatus(201);

        $laporan = LaporanAspirasi::first();
        $this->assertNotNull($laporan);
        $this->assertStringNotContainsString('<script>', $laporan->judul_laporan);
        $this->assertStringNotContainsString('<img', $laporan->teks_keluhan);
        $this->assertStringNotContainsString('<i>', $laporan->lokasi_kejadian ?? '');
    }

    public function test_public_tracking_with_malicious_query_returns_404(): void
    {
        $response = $this->getJson("/api/v1/laporan-aspirasi/track/' OR '1'='1");
        $response->assertStatus(404);
    }
}
