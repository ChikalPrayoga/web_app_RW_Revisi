<?php

declare(strict_types=1);

namespace Tests\Feature\PortalWarga;

use App\Enums\KategoriInformasi;
use App\Enums\RoleName;
use App\Enums\StatusInformasi;
use App\Models\InformasiPublik;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalWargaWebTest extends TestCase
{
    use RefreshDatabase;

    protected User $sekretaris;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $roleSekretaris = Role::where('name', RoleName::SEKRETARIS_RW->value)->firstOrFail();
        $this->sekretaris = User::factory()->create([
            'role_id' => $roleSekretaris->id,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_guest_can_access_portal_warga_homepage(): void
    {
        InformasiPublik::create([
            'judul' => 'Pengumuman Resmi Warga RW 047',
            'konten' => 'Isi pengumuman yang harus diketahui oleh seluruh warga.',
            'kategori' => KategoriInformasi::PENGUMUMAN->value,
            'tanggal_publikasi' => now()->toDateString(),
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        InformasiPublik::create([
            'judul' => 'Agenda Kerja Bakti Bersama',
            'konten' => 'Kerja bakti pembersihan saluran air lingkungan.',
            'kategori' => KategoriInformasi::AGENDA->value,
            'tanggal_publikasi' => now()->toDateString(),
            'tanggal_agenda' => now()->addDays(3)->toDateString(),
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('Portal Resmi Layanan Warga')
            ->assertSee('SIM Warga')
            ->assertSee('RW 047 Kelurahan Bahagia')
            ->assertSee('Surat Pengantar')
            ->assertSee('Lacak Pengajuan')
            ->assertSee('Pengumuman Resmi Warga RW 047')
            ->assertSee('Agenda Kerja Bakti Bersama');
    }

    public function test_guest_can_access_public_informasi_catalog(): void
    {
        InformasiPublik::create([
            'judul' => 'Pengumuman 1',
            'konten' => 'Konten pengumuman',
            'kategori' => KategoriInformasi::PENGUMUMAN->value,
            'tanggal_publikasi' => now()->toDateString(),
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        InformasiPublik::create([
            'judul' => 'Berita Kegiatan 17 Agustus',
            'konten' => 'Lomba peringatan HUT RI berjalan meriah.',
            'kategori' => KategoriInformasi::BERITA->value,
            'tanggal_publikasi' => now()->toDateString(),
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        $response = $this->get('/informasi');

        $response->assertStatus(200)
            ->assertSeeText('Informasi, Berita & Agenda RW 047', false)
            ->assertSee('Pengumuman 1')
            ->assertSee('Berita Kegiatan 17 Agustus');
    }

    public function test_guest_can_filter_informasi_by_category(): void
    {
        InformasiPublik::create([
            'judul' => 'Pengumuman Khusus RT',
            'konten' => 'Konten',
            'kategori' => KategoriInformasi::PENGUMUMAN->value,
            'tanggal_publikasi' => now()->toDateString(),
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        InformasiPublik::create([
            'judul' => 'Agenda Posyandu Balita',
            'konten' => 'Pemeriksaan rutin balita dan imunisasi.',
            'kategori' => KategoriInformasi::AGENDA->value,
            'tanggal_publikasi' => now()->toDateString(),
            'tanggal_agenda' => now()->addDays(7)->toDateString(),
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        $response = $this->get('/informasi?kategori=AGENDA');

        $response->assertStatus(200)
            ->assertSee('Agenda Posyandu Balita')
            ->assertDontSee('Pengumuman Khusus RT');
    }

    public function test_guest_can_view_public_informasi_detail(): void
    {
        $item = InformasiPublik::create([
            'judul' => 'Detail Informasi Untuk Warga',
            'konten' => 'Isi teks pengumuman yang panjang dan rinci untuk dipelajari seluruh warga RW 047.',
            'kategori' => KategoriInformasi::PENGUMUMAN->value,
            'tanggal_publikasi' => now()->toDateString(),
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        $response = $this->get("/informasi/{$item->id}");

        $response->assertStatus(200)
            ->assertSee('Detail Informasi Untuk Warga')
            ->assertSee('Isi teks pengumuman yang panjang dan rinci')
            ->assertSee('Kembali');
    }

    public function test_guest_cannot_view_draft_detail_via_public_portal(): void
    {
        $draft = InformasiPublik::create([
            'judul' => 'Rencana Pengumuman Masih Draft',
            'konten' => 'Teks draft internal.',
            'kategori' => KategoriInformasi::PENGUMUMAN->value,
            'tanggal_publikasi' => now()->toDateString(),
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::DRAFT->value,
        ]);

        $response = $this->get("/informasi/{$draft->id}");

        $response->assertStatus(404);
    }
}
