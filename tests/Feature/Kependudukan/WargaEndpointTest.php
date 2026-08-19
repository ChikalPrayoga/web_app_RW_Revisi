<?php

declare(strict_types=1);

namespace Tests\Feature\Kependudukan;

use App\Models\KartuKeluarga;
use App\Models\Role;
use App\Models\User;
use App\Models\Warga;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WargaEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $ketuaRt01;

    private User $ketuaRt02;

    private User $sekretarisRw;

    private User $wargaUser;

    private KartuKeluarga $kkRt01;

    private KartuKeluarga $kkRt02;

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

        $this->kkRt01 = KartuKeluarga::create([
            'no_kk' => '3216010101230012',
            'rt_code' => '001',
            'alamat_lengkap' => 'Jl. Mawar Blok C No. 12',
            'blok' => 'C',
            'nomor_rumah' => '12',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ]);

        $this->kkRt02 = KartuKeluarga::create([
            'no_kk' => '3216020202230099',
            'rt_code' => '002',
            'alamat_lengkap' => 'Jl. Melati Blok D No. 5',
            'blok' => 'D',
            'nomor_rumah' => '5',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ]);
    }

    public function test_create_warga_success_returns_201_and_menunggu_verifikasi(): void
    {
        Sanctum::actingAs($this->ketuaRt01);

        $payload = [
            'nik' => '3216011505900021',
            'no_kk' => '3216010101230012',
            'nama_lengkap' => 'Ahmad Fauzi',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1990-05-15',
            'pekerjaan' => 'Wiraswasta',
            'nomor_hp' => '081234500001',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
            'status_warga' => 'TETAP',
        ];

        $response = $this->postJson('/api/v1/warga', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Data warga berhasil ditambahkan, menunggu verifikasi Sekretaris RW',
                'data' => [
                    'nik_masked' => '3216xxxxxxxx0021',
                    'nama_lengkap' => 'Ahmad Fauzi',
                    'verification_status' => 'MENUNGGU_VERIFIKASI',
                ],
            ]);

        $this->assertDatabaseHas('wargas', [
            'kartu_keluarga_id' => $this->kkRt01->id,
            'nama_lengkap' => 'Ahmad Fauzi',
            'verification_status' => 'MENUNGGU_VERIFIKASI',
        ]);
    }

    public function test_create_warga_duplicate_nik_returns_409_conflict(): void
    {
        Sanctum::actingAs($this->ketuaRt01);

        $payload = [
            'nik' => '3216011505900021',
            'no_kk' => '3216010101230012',
            'nama_lengkap' => 'Ahmad Fauzi',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1990-05-15',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
        ];

        $this->postJson('/api/v1/warga', $payload)->assertStatus(201);

        // Duplicate
        $response = $this->postJson('/api/v1/warga', $payload);
        $response->assertStatus(409)
            ->assertJson([
                'message' => 'NIK sudah terdaftar pada sistem',
            ]);
    }

    public function test_create_warga_non_existent_no_kk_returns_422(): void
    {
        Sanctum::actingAs($this->ketuaRt01);

        $payload = [
            'nik' => '3216011505900021',
            'no_kk' => '9999999999999999', // Unknown KK
            'nama_lengkap' => 'Ahmad Fauzi',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1990-05-15',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
        ];

        $response = $this->postJson('/api/v1/warga', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['no_kk']);
    }

    public function test_ketua_rt_cannot_add_warga_to_other_rt_kk(): void
    {
        Sanctum::actingAs($this->ketuaRt01);

        // Try to add warga to RT 002's KK
        $payload = [
            'nik' => '3216011505900021',
            'no_kk' => '3216020202230099', // KK milik RT 002
            'nama_lengkap' => 'Ahmad Fauzi',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1990-05-15',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
        ];

        $response = $this->postJson('/api/v1/warga', $payload);

        $response->assertStatus(403);
    }

    public function test_get_warga_list_returns_paginated_data_and_masked_pii(): void
    {
        Warga::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'nik' => '3216011505900021',
            'no_kk' => $this->kkRt01->no_kk,
            'nama_lengkap' => 'Ahmad Fauzi',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1990-05-15',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
            'status_warga' => 'TETAP',
            'verification_status' => 'MENUNGGU_VERIFIKASI',
        ]);

        Sanctum::actingAs($this->sekretarisRw);

        $response = $this->getJson('/api/v1/warga');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daftar warga berhasil diambil',
                'data' => [
                    [
                        'nik_masked' => '3216xxxxxxxx0021',
                        'no_kk_masked' => '3216xxxxxxxx0012',
                        'nama_lengkap' => 'Ahmad Fauzi',
                        'rt_code' => '001',
                    ],
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'nik_hash',
                        'nik_masked',
                        'nama_lengkap',
                        'jenis_kelamin',
                        'tanggal_lahir',
                        'status_hubungan_keluarga',
                        'status_warga',
                        'verification_status',
                        'no_kk_masked',
                        'rt_code',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);

        // Explicitly assert that list response does NOT leak tempat_lahir, nomor_hp, or raw plaintext NIK/KK
        $jsonString = (string) $response->getContent();
        $this->assertStringNotContainsString('tempat_lahir', $jsonString);
        $this->assertStringNotContainsString('3216011505900021', $jsonString);
        $this->assertStringNotContainsString('3216010101230012', $jsonString);
    }

    public function test_get_warga_list_for_ketua_rt_is_area_scoped(): void
    {
        // Warga RT 001
        Warga::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'nik' => '3216011505900001',
            'no_kk' => $this->kkRt01->no_kk,
            'nama_lengkap' => 'Warga RT 01',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1990-05-15',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
        ]);

        // Warga RT 002
        Warga::create([
            'kartu_keluarga_id' => $this->kkRt02->id,
            'nik' => '3216011505900002',
            'no_kk' => $this->kkRt02->no_kk,
            'nama_lengkap' => 'Warga RT 02',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1990-05-15',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
        ]);

        Sanctum::actingAs($this->ketuaRt01);

        // Client attempts to pass rt_code=002, but is forced to 001
        $response = $this->getJson('/api/v1/warga?rt_code=002');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.nama_lengkap', 'Warga RT 01');
    }

    public function test_get_warga_detail_by_nik_hash_success(): void
    {
        $warga = Warga::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'nik' => '3216011505900421',
            'no_kk' => $this->kkRt01->no_kk,
            'nama_lengkap' => 'Andi Wijaya',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1990-04-21',
            'pekerjaan' => 'Wiraswasta',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
            'status_warga' => 'TETAP',
            'verification_status' => 'MENUNGGU_VERIFIKASI',
        ]);

        Sanctum::actingAs($this->ketuaRt01);

        $response = $this->getJson('/api/v1/warga/'.$warga->nik_hash);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nik_masked' => '3216xxxxxxxx0421',
                    'nama_lengkap' => 'Andi Wijaya',
                    'no_kk_masked' => '3216xxxxxxxx0012',
                ],
            ]);
    }

    public function test_get_warga_detail_other_rt_by_ketua_rt_rejected_403(): void
    {
        $wargaRt02 = Warga::create([
            'kartu_keluarga_id' => $this->kkRt02->id,
            'nik' => '3216011505900099',
            'no_kk' => $this->kkRt02->no_kk,
            'nama_lengkap' => 'Warga RT 2',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1990-04-21',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
        ]);

        Sanctum::actingAs($this->ketuaRt01);

        $response = $this->getJson('/api/v1/warga/'.$wargaRt02->nik_hash);

        $response->assertStatus(403);
    }

    public function test_update_warga_success(): void
    {
        $warga = Warga::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'nik' => '3216011505900421',
            'no_kk' => $this->kkRt01->no_kk,
            'nama_lengkap' => 'Andi Wijaya',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1990-04-21',
            'pekerjaan' => 'Wiraswasta',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
            'verification_status' => 'TERVERIFIKASI',
        ]);

        Sanctum::actingAs($this->ketuaRt01);

        $response = $this->patchJson('/api/v1/warga/'.$warga->nik_hash, [
            'pekerjaan' => 'Karyawan Swasta',
            'nomor_hp' => '081234567890',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Data warga berhasil diperbarui, menunggu verifikasi Sekretaris RW',
                'data' => [
                    'nik_masked' => '3216xxxxxxxx0421',
                    'verification_status' => 'MENUNGGU_VERIFIKASI',
                ],
            ]);

        $this->assertEquals('Karyawan Swasta', $warga->fresh()->pekerjaan);
    }

    public function test_sekretaris_rw_can_verify_warga_approved(): void
    {
        $warga = Warga::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'nik' => '3216011505900021',
            'no_kk' => $this->kkRt01->no_kk,
            'nama_lengkap' => 'Ahmad Fauzi',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1990-05-15',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
            'status_warga' => 'TETAP',
            'verification_status' => 'MENUNGGU_VERIFIKASI',
        ]);

        Sanctum::actingAs($this->sekretarisRw);

        $response = $this->patchJson('/api/v1/warga/'.$warga->nik_hash.'/verify', [
            'decision' => 'APPROVED',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nik_masked' => '3216xxxxxxxx0021',
                    'status_warga' => 'TETAP',
                    'verification_status' => 'TERVERIFIKASI',
                ],
            ]);

        $this->assertEquals('TERVERIFIKASI', $warga->fresh()->verification_status);
        $this->assertEquals($this->sekretarisRw->id, $warga->fresh()->verified_by_user_id);
    }

    public function test_sekretaris_rw_can_verify_warga_rejected(): void
    {
        $warga = Warga::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'nik' => '3216011505900021',
            'no_kk' => $this->kkRt01->no_kk,
            'nama_lengkap' => 'Ahmad Fauzi',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1990-05-15',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
            'verification_status' => 'MENUNGGU_VERIFIKASI',
        ]);

        Sanctum::actingAs($this->sekretarisRw);

        $response = $this->patchJson('/api/v1/warga/'.$warga->nik_hash.'/verify', [
            'decision' => 'REJECTED',
            'rejection_notes' => 'Data tidak sesuai dengan KTP',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'verification_status' => 'DITOLAK',
                ],
            ]);

        $this->assertEquals('DITOLAK', $warga->fresh()->verification_status);
        $this->assertEquals('Data tidak sesuai dengan KTP', $warga->fresh()->verification_notes);
    }

    public function test_verify_warga_by_non_sekretaris_rw_returns_403(): void
    {
        $warga = Warga::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'nik' => '3216011505900021',
            'no_kk' => $this->kkRt01->no_kk,
            'nama_lengkap' => 'Ahmad Fauzi',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1990-05-15',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
            'verification_status' => 'MENUNGGU_VERIFIKASI',
        ]);

        Sanctum::actingAs($this->ketuaRt01);

        $response = $this->patchJson('/api/v1/warga/'.$warga->nik_hash.'/verify', [
            'decision' => 'APPROVED',
        ]);

        $response->assertStatus(403);
    }

    public function test_verify_warga_already_verified_returns_409_conflict(): void
    {
        $warga = Warga::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'nik' => '3216011505900021',
            'no_kk' => $this->kkRt01->no_kk,
            'nama_lengkap' => 'Ahmad Fauzi',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1990-05-15',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
            'verification_status' => 'TERVERIFIKASI',
        ]);

        Sanctum::actingAs($this->sekretarisRw);

        $response = $this->patchJson('/api/v1/warga/'.$warga->nik_hash.'/verify', [
            'decision' => 'APPROVED',
        ]);

        $response->assertStatus(409);
    }

    /**
     * POLICY INVOCATION EVIDENCE
     *
     * Membuktikan bahwa WargaPolicy::view() benar-benar DIPANGGIL
     * oleh Gate/Policy framework, bukan hanya terdaftar.
     *
     * Mekanisme: Gate::inspect() mengembalikan Response yang menunjukkan
     * Policy method mana yang dieksekusi dan apa hasilnya.
     */
    public function test_warga_policy_view_is_invoked_via_gate_for_authorized_user(): void
    {
        $warga = Warga::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'nik' => '3216011505900021',
            'no_kk' => $this->kkRt01->no_kk,
            'nama_lengkap' => 'Ahmad Test',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1990-05-15',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
        ]);

        // Load kartuKeluarga agar Policy::view() dapat memeriksa rt_code
        $warga->load('kartuKeluarga');

        // KETUA_RT RT 001 → data warga berada di RT 001 → Policy HARUS mengizinkan
        $response = \Illuminate\Support\Facades\Gate::forUser($this->ketuaRt01)->inspect('view', [$warga]);
        $this->assertTrue(
            $response->allowed(),
            'WargaPolicy::view() harus mengizinkan KETUA_RT mengakses warga di RT-nya sendiri.'
        );

        // KETUA_RT RT 002 → data warga berada di RT 001 → Policy HARUS menolak
        $responseRt02 = \Illuminate\Support\Facades\Gate::forUser($this->ketuaRt02)->inspect('view', [$warga]);
        $this->assertFalse(
            $responseRt02->allowed(),
            'WargaPolicy::view() harus menolak KETUA_RT mengakses warga di RT lain.'
        );
    }

    /**
     * POLICY INVOCATION EVIDENCE — HTTP layer
     *
     * Membuktikan bahwa $this->authorize('view', $warga) di Controller
     * benar-benar menghasilkan 403 ketika KETUA_RT mencoba mengakses
     * warga di RT lain melalui HTTP request.
     */
    public function test_warga_policy_view_enforced_via_http_returns_403_for_other_rt(): void
    {
        $warga = Warga::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'nik' => '3216011505900088',
            'no_kk' => $this->kkRt01->no_kk,
            'nama_lengkap' => 'Budi Pakelik',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '1985-03-10',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
        ]);

        // KETUA_RT RT 002 mencoba akses warga RT 001 → Policy::view() → false → 403
        Sanctum::actingAs($this->ketuaRt02);
        $response = $this->getJson('/api/v1/warga/'.$warga->nik_hash);
        $response->assertStatus(403);
    }

    /**
     * FAILED AUTHORIZATION AUDIT REQUIREMENT (USER_STORIES.md §3.5)
     *
     * "Pengguna tanpa hak akses mencoba melihat data NIK/No. KK dalam bentuk unmasked
     *  → Sistem menolak dengan 403 Forbidden; percobaan akses tercatat di audit_logs
     *  terlepas dari berhasil/gagal."
     *
     * Bukti A: Unauthorized KETUA_RT mencoba GET detail warga dari RT lain → 403 + audit record tercatat.
     * Bukti B: Authorized user GET detail warga → 200 + audit VIEW_WARGA_DETAIL tercatat.
     * Bukti C: Audit record tidak mengandung plaintext NIK, No. KK, alamat, nomor HP.
     * Bukti D: Policy tetap bekerja dan authorization tidak dapat dibypass.
     */
    public function test_unauthorized_detail_access_returns_403_and_creates_sanitized_audit_log(): void
    {
        $rawNik = '3216011505900055';
        $rawNoKk = $this->kkRt01->no_kk;
        $alamat = 'Jl. Mawar Blok C No. 12';
        $noHp = '081234500099';

        $wargaRt01 = Warga::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'nik' => $rawNik,
            'no_kk' => $rawNoKk,
            'nama_lengkap' => 'Siti Nurhaliza',
            'jenis_kelamin' => 'P',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1992-07-20',
            'status_hubungan_keluarga' => 'Istri',
            'nomor_hp' => $noHp,
        ]);

        $auditCountBefore = \App\Models\AuditLog::count();

        // -------------------------------------------------------------
        // A. UNAUTHORIZED ACCESS (KETUA_RT RT 002 mencoba akses warga RT 001)
        // -------------------------------------------------------------
        Sanctum::actingAs($this->ketuaRt02);
        $response403 = $this->getJson('/api/v1/warga/'.$wargaRt01->nik_hash);

        // 1. Otorisasi menolak dengan 403 Forbidden (tidak ada bypass)
        $response403->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);

        // 2. Percobaan akses tercatat di audit_logs
        $auditCountAfter403 = \App\Models\AuditLog::count();
        $this->assertEquals(
            $auditCountBefore + 1,
            $auditCountAfter403,
            'Percobaan akses tidak sah (403) HARUS mencatat 1 baris audit log sesuai USER_STORIES.md §3.5.'
        );

        $unauthAudit = \App\Models\AuditLog::orderBy('id', 'desc')->first();
        $this->assertNotNull($unauthAudit);
        $this->assertEquals('Kependudukan', $unauthAudit->module);
        $this->assertEquals('UNAUTHORIZED_ACCESS_ATTEMPT', $unauthAudit->action);
        $this->assertEquals('wargas', $unauthAudit->entity_type);
        $this->assertEquals((string) $wargaRt01->id, $unauthAudit->entity_id);
        $this->assertEquals($this->ketuaRt02->id, $unauthAudit->user_id);
        $this->assertEquals('DENIED', $unauthAudit->new_values['outcome'] ?? null);
        $this->assertEquals(403, $unauthAudit->new_values['status_code'] ?? null);

        // 3. Bukti C: Audit record tidak mengandung plaintext sensitif (NIK, No. KK, HP, Alamat)
        $unauthAuditJson = json_encode($unauthAudit->toArray());
        $this->assertStringNotContainsString($rawNik, $unauthAuditJson, 'Audit log failed-access TIDAK boleh mengandung plaintext NIK.');
        $this->assertStringNotContainsString($rawNoKk, $unauthAuditJson, 'Audit log failed-access TIDAK boleh mengandung plaintext No. KK.');
        $this->assertStringNotContainsString($noHp, $unauthAuditJson, 'Audit log failed-access TIDAK boleh mengandung nomor HP.');

        // -------------------------------------------------------------
        // B. AUTHORIZED ACCESS (KETUA_RT RT 001 mengakses warga RT 001 miliknya)
        // -------------------------------------------------------------
        Sanctum::actingAs($this->ketuaRt01);
        $response200 = $this->getJson('/api/v1/warga/'.$wargaRt01->nik_hash);

        // 1. Respons berhasil 200 OK
        $response200->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nama_lengkap' => 'Siti Nurhaliza',
                ],
            ]);

        // 2. Audit log VIEW_WARGA_DETAIL tercatat
        $auditCountAfter200 = \App\Models\AuditLog::count();
        $this->assertEquals(
            $auditCountAfter403 + 1,
            $auditCountAfter200,
            'Akses yang sah (200 OK) HARUS mencatat 1 baris audit log VIEW_WARGA_DETAIL.'
        );

        $authAudit = \App\Models\AuditLog::orderBy('id', 'desc')->first();
        $this->assertNotNull($authAudit);
        $this->assertEquals('Kependudukan', $authAudit->module);
        $this->assertEquals('VIEW_WARGA_DETAIL', $authAudit->action);
        $this->assertEquals('wargas', $authAudit->entity_type);
        $this->assertEquals((string) $wargaRt01->id, $authAudit->entity_id);
        $this->assertEquals($this->ketuaRt01->id, $authAudit->user_id);

        // 3. Bukti C: Audit record authorized juga tidak mengandung plaintext NIK/No. KK
        $authAuditJson = json_encode($authAudit->toArray());
        $this->assertStringNotContainsString($rawNik, $authAuditJson, 'Audit log VIEW_WARGA_DETAIL TIDAK boleh mengandung plaintext NIK.');
        $this->assertStringNotContainsString($rawNoKk, $authAuditJson, 'Audit log VIEW_WARGA_DETAIL TIDAK boleh mengandung plaintext No. KK.');
    }
}
