<?php

declare(strict_types=1);

namespace Tests\Feature\LaporanAspirasi;

use App\Enums\RoleName;
use App\Enums\StatusLaporan;
use App\Models\LaporanAspirasi;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LaporanAspirasiEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $ketuaRt;

    private User $sekretarisRw;

    private User $wargaUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $roleRt = Role::where('name', RoleName::KETUA_RT->value)->firstOrFail();
        $roleSek = Role::where('name', RoleName::SEKRETARIS_RW->value)->firstOrFail();
        $roleWarga = Role::where('name', RoleName::WARGA->value)->firstOrFail();

        $this->ketuaRt = User::factory()->create([
            'role_id' => $roleRt->id,
            'status' => 'ACTIVE',
            'rt_code' => '001',
        ]);

        $this->sekretarisRw = User::factory()->create([
            'role_id' => $roleSek->id,
            'status' => 'ACTIVE',
        ]);

        $this->wargaUser = User::factory()->create([
            'role_id' => $roleWarga->id,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_post_laporan_aspirasi_public_success(): void
    {
        $payload = [
            'judul_laporan' => 'Jalan Rusak di Gang Mawar',
            'teks_keluhan' => 'Jalan berlubang cukup parah dan membahayakan pengendara motor saat malam hari.',
            'lokasi_kejadian' => 'Gang Mawar RT 02',
        ];

        $response = $this->postJson('/api/v1/laporan-aspirasi', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'current_status' => 'SUBMITTED',
                ],
            ]);

        $this->assertNotNull($response->json('data.ticket_number'));
        $this->assertDatabaseHas('laporan_aspirasis', [
            'judul_laporan' => 'Jalan Rusak di Gang Mawar',
            'current_status' => 'SUBMITTED',
        ]);
    }

    public function test_post_laporan_aspirasi_validation_error_short_description(): void
    {
        $payload = [
            'judul_laporan' => 'Lampu Mati',
            'teks_keluhan' => 'Terlalu pendek', // Kurang dari 20 karakter
        ];

        $response = $this->postJson('/api/v1/laporan-aspirasi', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['teks_keluhan']);
    }

    public function test_post_laporan_aspirasi_validation_error_missing_title(): void
    {
        $payload = [
            'teks_keluhan' => 'Ini teks keluhan yang sudah lebih dari dua puluh karakter.',
        ];

        $response = $this->postJson('/api/v1/laporan-aspirasi', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['judul_laporan']);
    }

    public function test_get_track_laporan_public_success(): void
    {
        $laporan = LaporanAspirasi::create([
            'ticket_number' => 'LPR-20260818-00001',
            'judul_laporan' => 'Saluran Air Mampet',
            'teks_keluhan' => 'Saluran air tersumbat sampah ranting pohon.',
            'current_status' => StatusLaporan::SUBMITTED,
            'submitted_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/laporan-aspirasi/track/LPR-20260818-00001');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'ticket_number' => 'LPR-20260818-00001',
                    'judul_laporan' => 'Saluran Air Mampet',
                    'current_status' => 'SUBMITTED',
                ],
            ]);
    }

    public function test_get_track_laporan_not_found(): void
    {
        $response = $this->getJson('/api/v1/laporan-aspirasi/track/LPR-INVALID-999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_get_laporan_list_authenticated_pengurus_success(): void
    {
        LaporanAspirasi::create([
            'ticket_number' => 'LPR-20260818-00001',
            'judul_laporan' => 'Laporan 1',
            'teks_keluhan' => 'Deskripsi keluhan nomor satu dengan panjang minimal.',
            'current_status' => StatusLaporan::SUBMITTED,
            'submitted_at' => now(),
        ]);

        Sanctum::actingAs($this->ketuaRt);

        $response = $this->getJson('/api/v1/laporan-aspirasi');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'aspirasi_id',
                        'ticket_number',
                        'judul_laporan',
                        'current_status',
                    ],
                ],
                'meta' => ['current_page', 'total'],
            ]);
    }

    public function test_get_laporan_list_unauthenticated_returns_401(): void
    {
        $response = $this->getJson('/api/v1/laporan-aspirasi');

        $response->assertStatus(401);
    }

    public function test_get_laporan_list_forbidden_role_returns_403(): void
    {
        Sanctum::actingAs($this->wargaUser);

        $response = $this->getJson('/api/v1/laporan-aspirasi');

        $response->assertStatus(403);
    }

    public function test_patch_laporan_status_by_pengurus_success(): void
    {
        $laporan = LaporanAspirasi::create([
            'ticket_number' => 'LPR-20260818-00001',
            'judul_laporan' => 'Laporan 1',
            'teks_keluhan' => 'Deskripsi keluhan nomor satu dengan panjang minimal.',
            'current_status' => StatusLaporan::SUBMITTED,
            'submitted_at' => now(),
        ]);

        Sanctum::actingAs($this->ketuaRt);

        $response = $this->patchJson("/api/v1/laporan-aspirasi/{$laporan->aspirasi_id}/status", [
            'current_status' => 'IN_PROGRESS',
            'catatan_tindak_lanjut' => 'Sedang dikoordinasikan.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'current_status' => 'IN_PROGRESS',
                ],
            ]);

        $this->assertDatabaseHas('laporan_aspirasis', [
            'aspirasi_id' => $laporan->aspirasi_id,
            'current_status' => 'IN_PROGRESS',
        ]);
    }

    public function test_patch_laporan_status_invalid_transition_returns_422(): void
    {
        $laporan = LaporanAspirasi::create([
            'ticket_number' => 'LPR-20260818-00001',
            'judul_laporan' => 'Laporan 1',
            'teks_keluhan' => 'Deskripsi keluhan nomor satu dengan panjang minimal.',
            'current_status' => StatusLaporan::SUBMITTED,
            'submitted_at' => now(),
        ]);

        Sanctum::actingAs($this->ketuaRt);

        // SUBMITTED tidak bisa langsung ke RESOLVED
        $response = $this->patchJson("/api/v1/laporan-aspirasi/{$laporan->aspirasi_id}/status", [
            'current_status' => 'RESOLVED',
            'catatan_tindak_lanjut' => 'Langsung beres',
        ]);

        $response->assertStatus(422);
    }

    public function test_delete_laporan_by_sekretaris_success(): void
    {
        $laporan = LaporanAspirasi::create([
            'ticket_number' => 'LPR-20260818-00001',
            'judul_laporan' => 'Laporan Dibatalkan',
            'teks_keluhan' => 'Deskripsi keluhan nomor satu dengan panjang minimal.',
            'current_status' => StatusLaporan::SUBMITTED,
            'submitted_at' => now(),
        ]);

        Sanctum::actingAs($this->sekretarisRw);

        $response = $this->deleteJson("/api/v1/laporan-aspirasi/{$laporan->aspirasi_id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('laporan_aspirasis', [
            'aspirasi_id' => $laporan->aspirasi_id,
        ]);
    }

    public function test_delete_laporan_by_unauthorized_role_returns_403(): void
    {
        $laporan = LaporanAspirasi::create([
            'ticket_number' => 'LPR-20260818-00001',
            'judul_laporan' => 'Laporan 1',
            'teks_keluhan' => 'Deskripsi keluhan nomor satu dengan panjang minimal.',
            'current_status' => StatusLaporan::SUBMITTED,
            'submitted_at' => now(),
        ]);

        Sanctum::actingAs($this->ketuaRt);

        $response = $this->deleteJson("/api/v1/laporan-aspirasi/{$laporan->aspirasi_id}");

        $response->assertStatus(403);
    }
}
