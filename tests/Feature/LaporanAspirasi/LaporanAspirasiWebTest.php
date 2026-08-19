<?php

declare(strict_types=1);

namespace Tests\Feature\LaporanAspirasi;

use App\Enums\RoleName;
use App\Enums\StatusLaporan;
use App\Models\LaporanAspirasi;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanAspirasiWebTest extends TestCase
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

    public function test_pengurus_can_access_laporan_index(): void
    {
        LaporanAspirasi::create([
            'ticket_number' => 'LPR-20260818-00001',
            'judul_laporan' => 'Jalan Rusak di Gang Mawar',
            'teks_keluhan' => 'Deskripsi keluhan nomor satu dengan panjang minimal.',
            'current_status' => StatusLaporan::SUBMITTED,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->ketuaRt)->get('/laporan-aspirasi');

        $response->assertStatus(200)
            ->assertSee('Laporan & Aspirasi Warga', false)
            ->assertSee('LPR-20260818-00001')
            ->assertSee('Jalan Rusak di Gang Mawar');
    }

    public function test_guest_cannot_access_laporan_index_redirects_to_login(): void
    {
        $response = $this->get('/laporan-aspirasi');

        $response->assertRedirect('/login');
    }

    public function test_warga_cannot_access_laporan_index_returns_403(): void
    {
        $response = $this->actingAs($this->wargaUser)->get('/laporan-aspirasi');

        $response->assertStatus(403);
    }

    public function test_pengurus_can_view_laporan_detail(): void
    {
        $laporan = LaporanAspirasi::create([
            'ticket_number' => 'LPR-20260818-00001',
            'judul_laporan' => 'Jalan Rusak di Gang Mawar',
            'teks_keluhan' => 'Deskripsi keluhan lengkap untuk laporan pengaduan warga.',
            'lokasi_kejadian' => 'Jl. Kenanga No. 10',
            'current_status' => StatusLaporan::SUBMITTED,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->sekretarisRw)->get("/laporan-aspirasi/{$laporan->aspirasi_id}");

        $response->assertStatus(200)
            ->assertSee('LPR-20260818-00001')
            ->assertSee('Jalan Rusak di Gang Mawar')
            ->assertSee('Deskripsi keluhan lengkap untuk laporan pengaduan warga.')
            ->assertSee('Jl. Kenanga No. 10');
    }

    public function test_pengurus_can_update_laporan_status(): void
    {
        $laporan = LaporanAspirasi::create([
            'ticket_number' => 'LPR-20260818-00001',
            'judul_laporan' => 'Jalan Rusak di Gang Mawar',
            'teks_keluhan' => 'Deskripsi keluhan lengkap untuk laporan pengaduan warga.',
            'current_status' => StatusLaporan::SUBMITTED,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->ketuaRt)->post("/laporan-aspirasi/{$laporan->aspirasi_id}/status", [
            'current_status' => 'IN_PROGRESS',
            'catatan_tindak_lanjut' => 'Sedang dikoordinasikan.',
        ]);

        $response->assertRedirect("/laporan-aspirasi/{$laporan->aspirasi_id}")
            ->assertSessionHas('success');

        $this->assertDatabaseHas('laporan_aspirasis', [
            'aspirasi_id' => $laporan->aspirasi_id,
            'current_status' => 'IN_PROGRESS',
        ]);
    }

    public function test_pengurus_update_invalid_status_redirects_with_error(): void
    {
        $laporan = LaporanAspirasi::create([
            'ticket_number' => 'LPR-20260818-00001',
            'judul_laporan' => 'Jalan Rusak di Gang Mawar',
            'teks_keluhan' => 'Deskripsi keluhan lengkap untuk laporan pengaduan warga.',
            'current_status' => StatusLaporan::SUBMITTED,
            'submitted_at' => now(),
        ]);

        // SUBMITTED lompat ke RESOLVED tanpa IN_PROGRESS
        $response = $this->actingAs($this->ketuaRt)->post("/laporan-aspirasi/{$laporan->aspirasi_id}/status", [
            'current_status' => 'RESOLVED',
            'catatan_tindak_lanjut' => 'Beres',
        ]);

        $response->assertRedirect("/laporan-aspirasi/{$laporan->aspirasi_id}")
            ->assertSessionHas('error');
    }
}
