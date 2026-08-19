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
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InformasiPublikEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected User $sekretaris;

    protected User $ketuaRt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $roleSekretaris = Role::where('name', RoleName::SEKRETARIS_RW->value)->firstOrFail();
        $this->sekretaris = User::factory()->create([
            'role_id' => $roleSekretaris->id,
            'status' => 'ACTIVE',
        ]);

        $roleRt = Role::where('name', RoleName::KETUA_RT->value)->firstOrFail();
        $this->ketuaRt = User::factory()->create([
            'role_id' => $roleRt->id,
            'rt_code' => '001',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_get_informasi_publik_list_public_access(): void
    {
        InformasiPublik::create([
            'judul' => 'Pengumuman Penting Publik',
            'konten' => 'Isi pengumuman lengkap',
            'kategori' => KategoriInformasi::PENGUMUMAN->value,
            'tanggal_publikasi' => '2026-08-10',
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        InformasiPublik::create([
            'judul' => 'Draft Rahasia Internal',
            'konten' => 'Isi draft',
            'kategori' => KategoriInformasi::PENGUMUMAN->value,
            'tanggal_publikasi' => '2026-08-10',
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::DRAFT->value,
        ]);

        $response = $this->getJson('/api/v1/informasi-publik');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Data informasi publik berhasil diambil',
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Pengumuman Penting Publik')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'judul',
                        'konten',
                        'kategori',
                        'kategori_label',
                        'tanggal_publikasi',
                        'status',
                    ],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    public function test_get_informasi_publik_filter_by_kategori(): void
    {
        InformasiPublik::create([
            'judul' => 'Berita Lingkungan',
            'konten' => 'Isi berita',
            'kategori' => KategoriInformasi::BERITA->value,
            'tanggal_publikasi' => '2026-08-10',
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        InformasiPublik::create([
            'judul' => 'Agenda Kerja Bakti',
            'konten' => 'Isi agenda',
            'kategori' => KategoriInformasi::AGENDA->value,
            'tanggal_publikasi' => '2026-08-10',
            'tanggal_agenda' => '2026-08-20',
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        $response = $this->getJson('/api/v1/informasi-publik?kategori=AGENDA');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Agenda Kerja Bakti');
    }

    public function test_get_informasi_publik_detail_success_for_published(): void
    {
        $item = InformasiPublik::create([
            'judul' => 'Detail Informasi Publik',
            'konten' => 'Isi lengkap yang dapat dibaca semua warga',
            'kategori' => KategoriInformasi::PENGUMUMAN->value,
            'tanggal_publikasi' => '2026-08-10',
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        $response = $this->getJson("/api/v1/informasi-publik/{$item->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $item->id,
                    'judul' => 'Detail Informasi Publik',
                ],
            ]);
    }

    public function test_get_informasi_publik_detail_404_for_draft(): void
    {
        $draft = InformasiPublik::create([
            'judul' => 'Draft Rahasia',
            'konten' => 'Isi draft',
            'kategori' => KategoriInformasi::PENGUMUMAN->value,
            'tanggal_publikasi' => '2026-08-10',
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::DRAFT->value,
        ]);

        $response = $this->getJson("/api/v1/informasi-publik/{$draft->id}");

        $response->assertStatus(404);
    }

    public function test_post_informasi_publik_by_sekretaris_rw_success(): void
    {
        Sanctum::actingAs($this->sekretaris);

        $payload = [
            'judul' => 'Jadwal Kerja Bakti Agustus 2026',
            'konten' => 'Kerja bakti bersama akan dilaksanakan pada hari Minggu di lapangan RW.',
            'kategori' => 'AGENDA',
            'tanggal_agenda' => '2026-08-17',
            'status' => 'PUBLISHED',
        ];

        $response = $this->postJson('/api/v1/informasi-publik', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Informasi publik berhasil dipublikasikan',
                'data' => [
                    'judul' => 'Jadwal Kerja Bakti Agustus 2026',
                    'status' => 'PUBLISHED',
                ],
            ]);

        $this->assertDatabaseHas('informasi_publiks', [
            'judul' => 'Jadwal Kerja Bakti Agustus 2026',
            'status' => 'PUBLISHED',
            'published_by_user_id' => $this->sekretaris->id,
        ]);
    }

    public function test_post_informasi_publik_validation_error(): void
    {
        Sanctum::actingAs($this->sekretaris);

        $payload = [
            'judul' => '',
            'kategori' => 'KATEGORI_PALSU',
        ];

        $response = $this->postJson('/api/v1/informasi-publik', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['judul', 'konten', 'kategori']);
    }

    public function test_post_informasi_publik_unauthorized_role_forbidden(): void
    {
        Sanctum::actingAs($this->ketuaRt);

        $payload = [
            'judul' => 'Mencoba Membuat Informasi Sebagai RT',
            'konten' => 'Konten',
            'kategori' => 'PENGUMUMAN',
            'status' => 'PUBLISHED',
        ];

        $response = $this->postJson('/api/v1/informasi-publik', $payload);

        $response->assertStatus(403);
    }

    public function test_patch_informasi_publik_success(): void
    {
        Sanctum::actingAs($this->sekretaris);

        $item = InformasiPublik::create([
            'judul' => 'Judul Sebelum Update',
            'konten' => 'Konten lama',
            'kategori' => KategoriInformasi::BERITA->value,
            'tanggal_publikasi' => '2026-08-10',
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::DRAFT->value,
        ]);

        $payload = [
            'judul' => 'Judul Setelah Update',
            'konten' => 'Konten baru yang diperbarui',
            'kategori' => 'BERITA',
            'status' => 'PUBLISHED',
        ];

        $response = $this->patchJson("/api/v1/informasi-publik/{$item->id}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'judul' => 'Judul Setelah Update',
                    'status' => 'PUBLISHED',
                ],
            ]);
    }

    public function test_delete_informasi_publik_success(): void
    {
        Sanctum::actingAs($this->sekretaris);

        $item = InformasiPublik::create([
            'judul' => 'Informasi Akan Dihapus API',
            'konten' => 'Konten',
            'kategori' => KategoriInformasi::PENGUMUMAN->value,
            'tanggal_publikasi' => '2026-08-10',
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        $response = $this->deleteJson("/api/v1/informasi-publik/{$item->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Informasi publik berhasil dihapus',
            ]);

        $this->assertSoftDeleted('informasi_publiks', ['id' => $item->id]);
    }
}
