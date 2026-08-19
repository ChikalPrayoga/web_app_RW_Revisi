<?php

declare(strict_types=1);

namespace Tests\Feature\Kependudukan;

use App\Models\AuditLog;
use App\Models\KartuKeluarga;
use App\Models\Role;
use App\Models\User;
use App\Models\Warga;
use App\Support\Security\DataEncryptionService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KependudukanSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $ketuaRt;

    private User $sekretarisRw;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $roleRt = Role::where('name', 'KETUA_RT')->firstOrFail();
        $roleSekretaris = Role::where('name', 'SEKRETARIS_RW')->firstOrFail();

        $this->ketuaRt = User::factory()->create([
            'role_id' => $roleRt->id,
            'email' => 'rt01_sec@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => '001',
            'status' => 'ACTIVE',
        ]);

        $this->sekretarisRw = User::factory()->create([
            'role_id' => $roleSekretaris->id,
            'email' => 'sek_sec@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => null,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_plaintext_nik_and_no_kk_are_not_stored_in_raw_database(): void
    {
        $rawNik = '3216011505900021';
        $rawNoKk = '3216010101230012';

        $kk = KartuKeluarga::create([
            'no_kk' => $rawNoKk,
            'rt_code' => '001',
            'alamat_lengkap' => 'Jl. Rahasia No. 1',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ]);

        $warga = Warga::create([
            'kartu_keluarga_id' => $kk->id,
            'nik' => $rawNik,
            'no_kk' => $rawNoKk,
            'nama_lengkap' => 'Warga Rahasia',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Kota Rahasia',
            'tanggal_lahir' => '1990-01-01',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
        ]);

        // Query directly via DB facade to bypass Eloquent decryption accessors
        $rawKkRow = DB::table('kartu_keluargas')->where('id', $kk->id)->first();
        $rawWargaRow = DB::table('wargas')->where('id', $warga->id)->first();

        // Ensure database does NOT contain raw plaintext
        $this->assertStringNotContainsString($rawNoKk, $rawKkRow->no_kk);
        $this->assertStringNotContainsString($rawNik, $rawWargaRow->nik);
        $this->assertStringNotContainsString($rawNoKk, $rawWargaRow->no_kk);
        $this->assertStringNotContainsString('Jl. Rahasia No. 1', $rawKkRow->alamat_lengkap);
        $this->assertStringNotContainsString('Kota Rahasia', $rawWargaRow->tempat_lahir);

        // Ensure deterministic hash matches
        $this->assertEquals(DataEncryptionService::deterministicHash($rawNoKk), $rawKkRow->no_kk_hash);
        $this->assertEquals(DataEncryptionService::deterministicHash($rawNik), $rawWargaRow->nik_hash);
    }

    public function test_soft_delete_kartu_keluarga_does_not_cascade_soft_delete_to_wargas(): void
    {
        $kk = KartuKeluarga::create([
            'no_kk' => '3216010101230012',
            'rt_code' => '001',
            'alamat_lengkap' => 'Jl. Mawar 1',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ]);

        $warga = Warga::create([
            'kartu_keluarga_id' => $kk->id,
            'nik' => '3216011505900021',
            'no_kk' => $kk->no_kk,
            'nama_lengkap' => 'Ahmad Fauzi',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1990-05-15',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
        ]);

        // Soft delete KK
        $kk->delete();

        $this->assertSoftDeleted('kartu_keluargas', ['id' => $kk->id]);

        // Warga MUST NOT be soft deleted (No automatic cascade)
        $this->assertDatabaseHas('wargas', [
            'id' => $warga->id,
            'deleted_at' => null,
        ]);
    }

    public function test_physical_fk_relational_integrity_is_enforced(): void
    {
        $kk = KartuKeluarga::create([
            'no_kk' => '3216010101230012',
            'rt_code' => '001',
            'alamat_lengkap' => 'Jl. Mawar 1',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ]);

        $warga = Warga::create([
            'kartu_keluarga_id' => $kk->id,
            'nik' => '3216011505900021',
            'no_kk' => $kk->no_kk,
            'nama_lengkap' => 'Ahmad Fauzi',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1990-05-15',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
        ]);

        $this->assertEquals($kk->id, $warga->kartu_keluarga_id);
        $this->assertEquals($kk->id, $warga->kartuKeluarga->id);
    }

    public function test_deterministic_hash_fails_fast_when_key_is_missing(): void
    {
        $originalKey = config('hashing.data_search_hash_key');
        config(['hashing.data_search_hash_key' => null]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DATA_SEARCH_HASH_KEY is not configured');

        try {
            DataEncryptionService::deterministicHash('3216011505900021');
        } finally {
            config(['hashing.data_search_hash_key' => $originalKey]);
        }
    }

    public function test_deterministic_hash_does_not_fallback_to_app_key(): void
    {
        $hashWithConfigKey = DataEncryptionService::deterministicHash('3216011505900021');
        $hashWithAppKey = hash_hmac('sha256', '3216011505900021', (string) config('app.key'));

        $this->assertNotEquals($hashWithAppKey, $hashWithConfigKey, 'Deterministic hash must use DATA_SEARCH_HASH_KEY, never APP_KEY.');
    }

    public function test_audit_log_does_not_store_plaintext_nik_or_no_kk(): void
    {
        $rawNik = '3216011505900021';
        $rawNoKk = '3216010101230012';

        $kk = KartuKeluarga::create([
            'no_kk' => $rawNoKk,
            'rt_code' => '001',
            'alamat_lengkap' => 'Jl. Mawar 1',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ]);

        $warga = Warga::create([
            'kartu_keluarga_id' => $kk->id,
            'nik' => $rawNik,
            'no_kk' => $rawNoKk,
            'nama_lengkap' => 'Ahmad Fauzi',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '1990-05-15',
            'status_hubungan_keluarga' => 'Kepala Keluarga',
        ]);

        $auditLogs = AuditLog::all();

        foreach ($auditLogs as $log) {
            $newValuesJson = json_encode($log->new_values);
            $oldValuesJson = json_encode($log->old_values);

            $this->assertStringNotContainsString($rawNik, (string) $newValuesJson);
            $this->assertStringNotContainsString($rawNoKk, (string) $newValuesJson);
            $this->assertStringNotContainsString($rawNik, (string) $oldValuesJson);
            $this->assertStringNotContainsString($rawNoKk, (string) $oldValuesJson);
        }
    }
}
