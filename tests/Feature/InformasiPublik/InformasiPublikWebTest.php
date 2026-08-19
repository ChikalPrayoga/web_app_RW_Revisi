<?php

declare(strict_types=1);

namespace Tests\Feature\InformasiPublik;

use App\Enums\KategoriInformasi;
use App\Enums\RoleName;
use App\Enums\StatusInformasi;
use App\Models\InformasiPublik;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InformasiPublikWebTest extends TestCase
{
    use RefreshDatabase;

    protected User $sekretaris;

    protected User $ketuaRw;

    protected User $warga;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $roleSekretaris = Role::where('name', RoleName::SEKRETARIS_RW->value)->firstOrFail();
        $this->sekretaris = User::factory()->create([
            'role_id' => $roleSekretaris->id,
            'status' => 'ACTIVE',
        ]);

        $roleRw = Role::where('name', RoleName::KETUA_RW->value)->firstOrFail();
        $this->ketuaRw = User::factory()->create([
            'role_id' => $roleRw->id,
            'status' => 'ACTIVE',
        ]);

        $roleWarga = Role::where('name', RoleName::WARGA->value)->firstOrFail();
        $this->warga = User::factory()->create([
            'role_id' => $roleWarga->id,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_guest_redirected_from_informasi_publik_management(): void
    {
        $response = $this->get('/informasi-publik');

        $response->assertRedirect('/login');
    }

    public function test_pengurus_can_access_informasi_publik_index(): void
    {
        InformasiPublik::create([
            'judul' => 'Judul Test Index Web',
            'konten' => 'Isi',
            'kategori' => KategoriInformasi::PENGUMUMAN->value,
            'tanggal_publikasi' => '2026-08-10',
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        $response = $this->actingAs($this->sekretaris)->get('/informasi-publik');

        $response->assertStatus(200)
            ->assertSee('Informasi Publik')
            ->assertSee('Judul Test Index Web');
    }

    public function test_sekretaris_can_access_create_form(): void
    {
        $response = $this->actingAs($this->sekretaris)->get('/informasi-publik/tambah');

        $response->assertStatus(200)
            ->assertSee('Tambah Informasi Baru')
            ->assertSee('Judul Informasi')
            ->assertSee('Kategori Konten');
    }

    public function test_sekretaris_can_submit_create_informasi(): void
    {
        $payload = [
            'judul' => 'Pengumuman Penting Baru Web',
            'konten' => 'Isi pengumuman lengkap dari form web',
            'kategori' => 'PENGUMUMAN',
            'tanggal_publikasi' => '2026-08-18',
            'status' => 'PUBLISHED',
        ];

        $response = $this->actingAs($this->sekretaris)->post('/informasi-publik', $payload);

        $response->assertRedirect('/informasi-publik')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('informasi_publiks', [
            'judul' => 'Pengumuman Penting Baru Web',
            'status' => 'PUBLISHED',
            'published_by_user_id' => $this->sekretaris->id,
        ]);
    }

    public function test_sekretaris_can_access_edit_form(): void
    {
        $item = InformasiPublik::create([
            'judul' => 'Judul Yang Akan Diedit',
            'konten' => 'Konten asli',
            'kategori' => KategoriInformasi::BERITA->value,
            'tanggal_publikasi' => '2026-08-10',
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::DRAFT->value,
        ]);

        $response = $this->actingAs($this->sekretaris)->get("/informasi-publik/{$item->id}/edit");

        $response->assertStatus(200)
            ->assertSee('Edit Informasi')
            ->assertSee('Judul Yang Akan Diedit');
    }

    public function test_sekretaris_can_update_informasi(): void
    {
        $item = InformasiPublik::create([
            'judul' => 'Judul Awal',
            'konten' => 'Konten awal',
            'kategori' => KategoriInformasi::BERITA->value,
            'tanggal_publikasi' => '2026-08-10',
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::DRAFT->value,
        ]);

        $payload = [
            'judul' => 'Judul Setelah Edit Web',
            'konten' => 'Konten baru',
            'kategori' => 'BERITA',
            'status' => 'PUBLISHED',
        ];

        $response = $this->actingAs($this->sekretaris)->put("/informasi-publik/{$item->id}", $payload);

        $response->assertRedirect('/informasi-publik')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('informasi_publiks', [
            'id' => $item->id,
            'judul' => 'Judul Setelah Edit Web',
            'status' => 'PUBLISHED',
        ]);
    }

    public function test_sekretaris_can_delete_informasi(): void
    {
        $item = InformasiPublik::create([
            'judul' => 'Judul Akan Dihapus',
            'konten' => 'Konten',
            'kategori' => KategoriInformasi::PENGUMUMAN->value,
            'tanggal_publikasi' => '2026-08-10',
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        $response = $this->actingAs($this->sekretaris)->delete("/informasi-publik/{$item->id}");

        $response->assertRedirect('/informasi-publik')
            ->assertSessionHas('success');

        $this->assertSoftDeleted('informasi_publiks', ['id' => $item->id]);
    }

    public function test_warga_cannot_access_create_form(): void
    {
        $response = $this->actingAs($this->warga)->get('/informasi-publik/tambah');

        $response->assertStatus(403);
    }
}
