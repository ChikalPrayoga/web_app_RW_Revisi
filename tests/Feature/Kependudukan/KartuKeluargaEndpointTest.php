<?php

declare(strict_types=1);

namespace Tests\Feature\Kependudukan;

use App\Models\KartuKeluarga;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KartuKeluargaEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $ketuaRt01;

    private User $ketuaRt02;

    private User $sekretarisRw;

    private User $wargaUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $roleRt = Role::where('name', 'KETUA_RT')->firstOrFail();
        $roleSekretaris = Role::where('name', 'SEKRETARIS_RW')->firstOrFail();
        $roleWarga = Role::where('name', 'WARGA')->firstOrFail();

        $this->ketuaRt01 = User::factory()->create([
            'role_id' => $roleRt->id,
            'email' => 'rt01@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => '001',
            'status' => 'ACTIVE',
        ]);

        $this->ketuaRt02 = User::factory()->create([
            'role_id' => $roleRt->id,
            'email' => 'rt02@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => '002',
            'status' => 'ACTIVE',
        ]);

        $this->sekretarisRw = User::factory()->create([
            'role_id' => $roleSekretaris->id,
            'email' => 'sekretaris@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => null,
            'status' => 'ACTIVE',
        ]);

        $this->wargaUser = User::factory()->create([
            'role_id' => $roleWarga->id,
            'email' => 'warga@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => '001',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_ketua_rt_can_create_kartu_keluarga_successfully(): void
    {
        Sanctum::actingAs($this->ketuaRt01);

        $payload = [
            'no_kk' => '3216010101230012',
            'rt_code' => '001',
            'alamat_lengkap' => 'Jl. Mawar Blok C No. 12, RT 001/RW 047',
            'blok' => 'C',
            'nomor_rumah' => '12',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ];

        $response = $this->postJson('/api/v1/kartu-keluarga', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Data Kartu Keluarga berhasil didaftarkan',
                'data' => [
                    'no_kk_masked' => '3216xxxxxxxx0012',
                    'rt_code' => '001',
                    'status_kepemilikan_rumah' => 'Milik Sendiri',
                ],
            ]);

        $this->assertDatabaseHas('kartu_keluargas', [
            'rt_code' => '001',
            'blok' => 'C',
        ]);
    }

    public function test_duplicate_no_kk_returns_409_conflict(): void
    {
        Sanctum::actingAs($this->ketuaRt01);

        $payload = [
            'no_kk' => '3216010101230012',
            'rt_code' => '001',
            'alamat_lengkap' => 'Jl. Mawar Blok C No. 12',
            'blok' => 'C',
            'nomor_rumah' => '12',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ];

        // First insert
        $this->postJson('/api/v1/kartu-keluarga', $payload)->assertStatus(201);

        // Second insert with same No KK
        $response = $this->postJson('/api/v1/kartu-keluarga', $payload);

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Nomor Kartu Keluarga sudah terdaftar pada sistem',
            ]);
    }

    public function test_ketua_rt_list_kartu_keluarga_is_area_scoped_to_own_rt(): void
    {
        // KK for RT 001
        KartuKeluarga::create([
            'no_kk' => '3216010101230001',
            'rt_code' => '001',
            'alamat_lengkap' => 'Jl. Mawar 1',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ]);

        // KK for RT 002
        KartuKeluarga::create([
            'no_kk' => '3216010101230002',
            'rt_code' => '002',
            'alamat_lengkap' => 'Jl. Melati 2',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ]);

        Sanctum::actingAs($this->ketuaRt01);

        // Even if client passes rt_code=002, Ketua RT 001 is forced to RT 001
        $response = $this->getJson('/api/v1/kartu-keluarga?rt_code=002');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.rt_code', '001');
    }

    public function test_sekretaris_rw_can_view_all_kartu_keluarga(): void
    {
        KartuKeluarga::create([
            'no_kk' => '3216010101230001',
            'rt_code' => '001',
            'alamat_lengkap' => 'Jl. Mawar 1',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ]);

        KartuKeluarga::create([
            'no_kk' => '3216010101230002',
            'rt_code' => '002',
            'alamat_lengkap' => 'Jl. Melati 2',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ]);

        Sanctum::actingAs($this->sekretarisRw);

        $response = $this->getJson('/api/v1/kartu-keluarga');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_warga_role_cannot_access_kartu_keluarga_endpoint(): void
    {
        Sanctum::actingAs($this->wargaUser);

        $this->getJson('/api/v1/kartu-keluarga')->assertStatus(403);
        $this->postJson('/api/v1/kartu-keluarga', [])->assertStatus(403);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/kartu-keluarga')->assertStatus(401);
        $this->postJson('/api/v1/kartu-keluarga', [])->assertStatus(401);
    }
}
