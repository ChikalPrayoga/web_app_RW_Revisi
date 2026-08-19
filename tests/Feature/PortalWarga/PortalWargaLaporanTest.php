<?php

declare(strict_types=1);

namespace Tests\Feature\PortalWarga;

use App\Enums\StatusLaporan;
use App\Models\LaporanAspirasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalWargaLaporanTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_laporan_create_form(): void
    {
        $response = $this->get('/laporan-aspirasi/ajukan');

        $response->assertStatus(200)
            ->assertSee('Sampaikan Laporan & Aspirasi', false)
            ->assertSee('Judul Laporan / Keluhan')
            ->assertSee('Rincian Laporan / Keluhan');
    }

    public function test_guest_can_submit_laporan_and_redirects_to_success(): void
    {
        $payload = [
            'judul_laporan' => 'Lampu Jalan Padam di RT 01',
            'teks_keluhan' => 'Lampu penerangan jalan di tiang nomor 5 mati sejak kemarin malam.',
            'lokasi_kejadian' => 'Jl. Kenanga Depan No. 10',
        ];

        $response = $this->post('/laporan-aspirasi/ajukan', $payload);

        $laporan = LaporanAspirasi::first();
        $this->assertNotNull($laporan);

        $response->assertRedirect("/laporan-aspirasi/sukses/{$laporan->ticket_number}")
            ->assertSessionHas('success');
    }

    public function test_guest_can_view_success_page(): void
    {
        $response = $this->get('/laporan-aspirasi/sukses/LPR-20260818-00001');

        $response->assertStatus(200)
            ->assertSee('Laporan Anda Berhasil Terkirim!')
            ->assertSee('LPR-20260818-00001');
    }

    public function test_guest_can_access_tracking_form(): void
    {
        $response = $this->get('/laporan-aspirasi/lacak');

        $response->assertStatus(200)
            ->assertSee('Lacak Laporan & Aspirasi', false)
            ->assertSee('Nomor Tiket Laporan');
    }

    public function test_guest_can_track_valid_ticket(): void
    {
        $laporan = LaporanAspirasi::create([
            'ticket_number' => 'LPR-20260818-00001',
            'judul_laporan' => 'Lampu Jalan Padam di RT 01',
            'teks_keluhan' => 'Lampu penerangan jalan di tiang nomor 5 mati sejak kemarin malam.',
            'lokasi_kejadian' => 'Jl. Kenanga Depan No. 10',
            'current_status' => StatusLaporan::SUBMITTED,
            'submitted_at' => now(),
        ]);

        $response = $this->get("/laporan-aspirasi/lacak/{$laporan->ticket_number}");

        $response->assertStatus(200)
            ->assertSee('LPR-20260818-00001')
            ->assertSee('Lampu Jalan Padam di RT 01')
            ->assertSee('Jl. Kenanga Depan No. 10')
            ->assertSee('Diterima');
    }

    public function test_guest_tracking_invalid_ticket_redirects_with_error(): void
    {
        $response = $this->get('/laporan-aspirasi/lacak/LPR-INVALID-999');

        $response->assertRedirect('/laporan-aspirasi/lacak')
            ->assertSessionHas('error');
    }
}
