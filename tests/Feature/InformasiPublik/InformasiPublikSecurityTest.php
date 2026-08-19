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

class InformasiPublikSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $sekretaris;

    protected User $ketuaRw;

    protected User $superAdmin;

    protected User $bendahara;

    protected User $ketuaRt;

    protected User $warga;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $roleSekretaris = Role::where('name', RoleName::SEKRETARIS_RW->value)->firstOrFail();
        $this->sekretaris = User::factory()->create(['role_id' => $roleSekretaris->id, 'status' => 'ACTIVE']);

        $roleRw = Role::where('name', RoleName::KETUA_RW->value)->firstOrFail();
        $this->ketuaRw = User::factory()->create(['role_id' => $roleRw->id, 'status' => 'ACTIVE']);

        $roleAdmin = Role::where('name', RoleName::SUPER_ADMIN->value)->firstOrFail();
        $this->superAdmin = User::factory()->create(['role_id' => $roleAdmin->id, 'status' => 'ACTIVE']);

        $roleBendahara = Role::where('name', RoleName::BENDAHARA_RW->value)->firstOrFail();
        $this->bendahara = User::factory()->create(['role_id' => $roleBendahara->id, 'status' => 'ACTIVE']);

        $roleRt = Role::where('name', RoleName::KETUA_RT->value)->firstOrFail();
        $this->ketuaRt = User::factory()->create(['role_id' => $roleRt->id, 'rt_code' => '001', 'status' => 'ACTIVE']);

        $roleWarga = Role::where('name', RoleName::WARGA->value)->firstOrFail();
        $this->warga = User::factory()->create(['role_id' => $roleWarga->id, 'status' => 'ACTIVE']);
    }

    public function test_mutation_rbac_matrix_for_create_informasi(): void
    {
        $payload = [
            'judul' => 'Judul Tes Matrix Otorisasi',
            'konten' => 'Konten tes',
            'kategori' => 'PENGUMUMAN',
            'status' => 'PUBLISHED',
        ];

        // Allowed Roles (SEKRETARIS_RW, KETUA_RW, SUPER_ADMIN)
        Sanctum::actingAs($this->sekretaris);
        $this->postJson('/api/v1/informasi-publik', $payload)->assertStatus(201);

        Sanctum::actingAs($this->ketuaRw);
        $this->postJson('/api/v1/informasi-publik', $payload)->assertStatus(201);

        Sanctum::actingAs($this->superAdmin);
        $this->postJson('/api/v1/informasi-publik', $payload)->assertStatus(201);

        // Forbidden Roles (BENDAHARA_RW, KETUA_RT, WARGA)
        Sanctum::actingAs($this->bendahara);
        $this->postJson('/api/v1/informasi-publik', $payload)->assertStatus(403);

        Sanctum::actingAs($this->ketuaRt);
        $this->postJson('/api/v1/informasi-publik', $payload)->assertStatus(403);

        Sanctum::actingAs($this->warga);
        $this->postJson('/api/v1/informasi-publik', $payload)->assertStatus(403);
    }

    public function test_mutation_rbac_matrix_for_update_informasi(): void
    {
        $item = InformasiPublik::create([
            'judul' => 'Item Original',
            'konten' => 'Konten original',
            'kategori' => KategoriInformasi::PENGUMUMAN->value,
            'tanggal_publikasi' => now()->toDateString(),
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::DRAFT->value,
        ]);

        $updatePayload = [
            'judul' => 'Item Diperbarui',
            'konten' => 'Konten diperbarui',
            'kategori' => 'PENGUMUMAN',
            'status' => 'PUBLISHED',
        ];

        // Forbidden Roles
        Sanctum::actingAs($this->bendahara);
        $this->patchJson("/api/v1/informasi-publik/{$item->id}", $updatePayload)->assertStatus(403);

        Sanctum::actingAs($this->ketuaRt);
        $this->patchJson("/api/v1/informasi-publik/{$item->id}", $updatePayload)->assertStatus(403);

        Sanctum::actingAs($this->warga);
        $this->patchJson("/api/v1/informasi-publik/{$item->id}", $updatePayload)->assertStatus(403);

        // Allowed Role
        Sanctum::actingAs($this->sekretaris);
        $this->patchJson("/api/v1/informasi-publik/{$item->id}", $updatePayload)->assertStatus(200);
    }

    public function test_mutation_rbac_matrix_for_delete_informasi(): void
    {
        $item = InformasiPublik::create([
            'judul' => 'Item Untuk Dihapus',
            'konten' => 'Konten',
            'kategori' => KategoriInformasi::PENGUMUMAN->value,
            'tanggal_publikasi' => now()->toDateString(),
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        // Forbidden Roles
        Sanctum::actingAs($this->ketuaRt);
        $this->deleteJson("/api/v1/informasi-publik/{$item->id}")->assertStatus(403);

        Sanctum::actingAs($this->warga);
        $this->deleteJson("/api/v1/informasi-publik/{$item->id}")->assertStatus(403);

        // Allowed Role
        Sanctum::actingAs($this->ketuaRw);
        $this->deleteJson("/api/v1/informasi-publik/{$item->id}")->assertStatus(200);
    }

    public function test_soft_deleted_item_cannot_be_retrieved_by_public(): void
    {
        $item = InformasiPublik::create([
            'judul' => 'Informasi Dihapus',
            'konten' => 'Konten',
            'kategori' => KategoriInformasi::PENGUMUMAN->value,
            'tanggal_publikasi' => now()->toDateString(),
            'published_by_user_id' => $this->sekretaris->id,
            'status' => StatusInformasi::PUBLISHED->value,
        ]);

        $item->delete();

        // API
        $this->getJson("/api/v1/informasi-publik/{$item->id}")->assertStatus(404);

        // Web
        $this->get("/informasi/{$item->id}")->assertStatus(404);
    }
}
