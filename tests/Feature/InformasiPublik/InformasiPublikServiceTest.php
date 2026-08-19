<?php

declare(strict_types=1);

namespace Tests\Feature\InformasiPublik;

use App\Enums\KategoriInformasi;
use App\Enums\RoleName;
use App\Enums\StatusInformasi;
use App\Models\InformasiPublik;
use App\Models\Role;
use App\Models\User;
use App\Modules\InformasiPublik\Services\InformasiPublikService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class InformasiPublikServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $sekretaris;

    protected InformasiPublikService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $roleSekretaris = Role::where('name', RoleName::SEKRETARIS_RW->value)->firstOrFail();
        $this->sekretaris = User::factory()->create([
            'role_id' => $roleSekretaris->id,
            'status' => 'ACTIVE',
        ]);

        $this->service = app(InformasiPublikService::class);
    }

    public function test_create_informasi_publik_success_and_logs_audit(): void
    {
        $data = [
            'judul' => 'Jadwal Fogging Nyamuk DBD RW 047',
            'konten' => 'Kegiatan fogging akan dilaksanakan di seluruh lingkungan RT 001 - 003.',
            'kategori' => KategoriInformasi::PENGUMUMAN->value,
            'status' => StatusInformasi::PUBLISHED->value,
        ];

        $informasi = $this->service->create($this->sekretaris, $data, '127.0.0.1');

        $this->assertInstanceOf(InformasiPublik::class, $informasi);
        $this->assertEquals('Jadwal Fogging Nyamuk DBD RW 047', $informasi->judul);
        $this->assertEquals(StatusInformasi::PUBLISHED, $informasi->status);
        $this->assertEquals($this->sekretaris->id, $informasi->published_by_user_id);
        $this->assertEquals(now()->toDateString(), $informasi->tanggal_publikasi->toDateString());

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Informasi Publik',
            'action' => 'CREATE_INFORMASI_PUBLIK',
            'user_id' => $this->sekretaris->id,
            'entity_id' => (string) $informasi->id,
        ]);
    }

    public function test_update_informasi_publik_success_and_logs_audit(): void
    {
        $informasi = InformasiPublik::create([
            'judul' => 'Judul Lama',
            'konten' => 'Konten lama',
            'kategori' => KategoriInformasi::BERITA->value,
            'tanggal_publikasi' => now()->toDateString(),
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::DRAFT->value,
        ]);

        $updateData = [
            'judul' => 'Judul Baru yang Diperbarui',
            'konten' => 'Konten baru yang lebih lengkap',
            'kategori' => KategoriInformasi::BERITA->value,
            'status' => StatusInformasi::PUBLISHED->value,
        ];

        $updated = $this->service->update($this->sekretaris, $informasi, $updateData, '127.0.0.1');

        $this->assertEquals('Judul Baru yang Diperbarui', $updated->judul);
        $this->assertEquals(StatusInformasi::PUBLISHED, $updated->status);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Informasi Publik',
            'action' => 'UPDATE_INFORMASI_PUBLIK',
            'user_id' => $this->sekretaris->id,
            'entity_id' => (string) $informasi->id,
        ]);
    }

    public function test_delete_soft_deletes_and_logs_audit(): void
    {
        $informasi = InformasiPublik::create([
            'judul' => 'Informasi yang Akan Dihapus',
            'konten' => 'Isi informasi',
            'kategori' => KategoriInformasi::PENGUMUMAN->value,
            'tanggal_publikasi' => now()->toDateString(),
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        $result = $this->service->delete($this->sekretaris, $informasi, '127.0.0.1');

        $this->assertTrue($result);
        $this->assertSoftDeleted('informasi_publiks', ['id' => $informasi->id]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Informasi Publik',
            'action' => 'DELETE_INFORMASI_PUBLIK',
            'user_id' => $this->sekretaris->id,
            'entity_id' => (string) $informasi->id,
        ]);
    }

    public function test_list_public_only_returns_published_content(): void
    {
        InformasiPublik::create([
            'judul' => 'Konten Published 1',
            'konten' => 'Isi',
            'kategori' => KategoriInformasi::PENGUMUMAN->value,
            'tanggal_publikasi' => now()->toDateString(),
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        InformasiPublik::create([
            'judul' => 'Konten Draft yang Rahasia',
            'konten' => 'Isi',
            'kategori' => KategoriInformasi::PENGUMUMAN->value,
            'tanggal_publikasi' => now()->toDateString(),
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::DRAFT->value,
        ]);

        InformasiPublik::create([
            'judul' => 'Konten Archived',
            'konten' => 'Isi',
            'kategori' => KategoriInformasi::BERITA->value,
            'tanggal_publikasi' => now()->subMonth()->toDateString(),
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::ARCHIVED->value,
        ]);

        $publicList = $this->service->listPublic();

        $this->assertCount(1, $publicList->items());
        $this->assertEquals('Konten Published 1', $publicList->items()[0]->judul);
    }

    public function test_list_public_filters_by_category(): void
    {
        InformasiPublik::create([
            'judul' => 'Pengumuman Kerja Bakti',
            'konten' => 'Isi',
            'kategori' => KategoriInformasi::PENGUMUMAN->value,
            'tanggal_publikasi' => now()->toDateString(),
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        InformasiPublik::create([
            'judul' => 'Agenda Rapat RW',
            'konten' => 'Isi',
            'kategori' => KategoriInformasi::AGENDA->value,
            'tanggal_publikasi' => now()->toDateString(),
            'tanggal_agenda' => now()->addDays(5)->toDateString(),
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        $agendaList = $this->service->listPublic(['kategori' => KategoriInformasi::AGENDA->value]);

        $this->assertCount(1, $agendaList->items());
        $this->assertEquals('Agenda Rapat RW', $agendaList->items()[0]->judul);
    }

    public function test_get_public_item_throws_404_for_draft(): void
    {
        $draft = InformasiPublik::create([
            'judul' => 'Rencana Pengumuman',
            'konten' => 'Draft',
            'kategori' => KategoriInformasi::PENGUMUMAN->value,
            'tanggal_publikasi' => now()->toDateString(),
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::DRAFT->value,
        ]);

        $this->expectException(NotFoundHttpException::class);
        $this->service->getPublicItem($draft->id);
    }

    public function test_get_upcoming_agendas(): void
    {
        InformasiPublik::create([
            'judul' => 'Agenda Besok',
            'konten' => 'Isi',
            'kategori' => KategoriInformasi::AGENDA->value,
            'tanggal_publikasi' => now()->toDateString(),
            'tanggal_agenda' => now()->addDay()->toDateString(),
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        InformasiPublik::create([
            'judul' => 'Agenda Kemarin',
            'konten' => 'Isi',
            'kategori' => KategoriInformasi::AGENDA->value,
            'tanggal_publikasi' => now()->subWeek()->toDateString(),
            'tanggal_agenda' => now()->subDay()->toDateString(),
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        $upcoming = $this->service->getUpcomingAgendas();

        $this->assertCount(1, $upcoming);
        $this->assertEquals('Agenda Besok', $upcoming[0]->judul);
    }

    public function test_get_public_stats(): void
    {
        InformasiPublik::create([
            'judul' => 'Pengumuman 1',
            'konten' => 'Isi',
            'kategori' => KategoriInformasi::PENGUMUMAN->value,
            'tanggal_publikasi' => now()->toDateString(),
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        InformasiPublik::create([
            'judul' => 'Berita 1',
            'konten' => 'Isi',
            'kategori' => KategoriInformasi::BERITA->value,
            'tanggal_publikasi' => now()->toDateString(),
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        $stats = $this->service->getPublicStats();

        $this->assertEquals(1, $stats['total_pengumuman']);
        $this->assertEquals(1, $stats['total_berita']);
        $this->assertEquals(0, $stats['total_agenda']);
    }
}
