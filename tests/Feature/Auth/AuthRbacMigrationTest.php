<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthRbacMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test all expected tables exist in database.
     */
    public function test_auth_and_rbac_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('roles'), 'Table roles should exist');
        $this->assertTrue(Schema::hasTable('permissions'), 'Table permissions should exist');
        $this->assertTrue(Schema::hasTable('users'), 'Table users should exist');
        $this->assertTrue(Schema::hasTable('role_permissions'), 'Table role_permissions should exist');
        $this->assertTrue(Schema::hasTable('audit_logs'), 'Table audit_logs should exist');
    }

    /**
     * Test table columns match the specifications.
     */
    public function test_table_columns_match_spec(): void
    {
        // Check roles columns
        $this->assertTrue(Schema::hasColumns('roles', [
            'id', 'name', 'display_name', 'description', 'created_at', 'updated_at',
        ]));

        // Check permissions columns
        $this->assertTrue(Schema::hasColumns('permissions', [
            'id', 'name', 'module', 'created_at', 'updated_at',
        ]));

        // Check users columns
        $this->assertTrue(Schema::hasColumns('users', [
            'id', 'role_id', 'username', 'full_name', 'email', 'password',
            'phone_number', 'rt_code', 'status', 'last_login_at',
            'created_at', 'updated_at', 'deleted_at',
        ]));

        // Check role_permissions columns
        $this->assertTrue(Schema::hasColumns('role_permissions', [
            'id', 'role_id', 'permission_id',
        ]));

        // Check audit_logs columns
        $this->assertTrue(Schema::hasColumns('audit_logs', [
            'id', 'user_id', 'module', 'action', 'entity_type', 'entity_id',
            'old_values', 'new_values', 'ip_address', 'created_at',
        ]));
    }

    /**
     * Test unique constraints on roles, permissions, and users.
     */
    public function test_unique_constraints(): void
    {
        $role = Role::create([
            'name' => 'SUPER_ADMIN',
            'display_name' => 'Super Admin',
        ]);

        // Duplicate role name should throw QueryException
        $this->expectException(QueryException::class);
        Role::create([
            'name' => 'SUPER_ADMIN',
            'display_name' => 'Duplicate Admin',
        ]);
    }

    /**
     * Test unique constraint on users username and email.
     */
    public function test_user_unique_constraints(): void
    {
        $role = Role::create([
            'name' => 'WARGA',
            'display_name' => 'Warga',
        ]);

        User::create([
            'role_id' => $role->id,
            'username' => 'warga01',
            'full_name' => 'Warga Pertama',
            'email' => 'warga01@rw047.id',
            'password' => 'password123',
            'status' => 'ACTIVE',
        ]);

        // Duplicate email should throw QueryException
        $this->expectException(QueryException::class);
        User::create([
            'role_id' => $role->id,
            'username' => 'warga02',
            'full_name' => 'Warga Kedua',
            'email' => 'warga01@rw047.id',
            'password' => 'password123',
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * Test user soft delete functionality.
     */
    public function test_user_soft_delete(): void
    {
        $role = Role::create([
            'name' => 'WARGA',
            'display_name' => 'Warga',
        ]);

        $user = User::create([
            'role_id' => $role->id,
            'username' => 'warga_hapus',
            'full_name' => 'Warga Hapus',
            'email' => 'warga_hapus@rw047.id',
            'password' => 'password123',
            'status' => 'ACTIVE',
        ]);

        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertNotNull(User::withTrashed()->find($user->id));
        $this->assertNull(User::find($user->id));
    }

    /**
     * Test RoleSeeder creates exact 6 roles.
     */
    public function test_role_seeder_creates_exact_six_roles(): void
    {
        $this->seed(RoleSeeder::class);

        $expectedRoles = [
            'WARGA',
            'KETUA_RT',
            'SEKRETARIS_RW',
            'BENDAHARA_RW',
            'KETUA_RW',
            'SUPER_ADMIN',
        ];

        $this->assertDatabaseCount('roles', 6);

        foreach ($expectedRoles as $roleName) {
            $this->assertDatabaseHas('roles', ['name' => $roleName]);
        }
    }

    /**
     * Test PermissionSeeder creates permissions and attaches them to roles.
     */
    public function test_permission_seeder_maps_rbac_matrix(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $superAdmin = Role::where('name', 'SUPER_ADMIN')->first();
        $this->assertNotNull($superAdmin);
        $this->assertGreaterThan(0, $superAdmin->permissions()->count());

        $bendahara = Role::where('name', 'BENDAHARA_RW')->first();
        $this->assertNotNull($bendahara);
        $this->assertTrue($bendahara->hasPermission('keuangan.approve'));
        $this->assertFalse($bendahara->hasPermission('surat.verify.rw'));

        $ketuaRt = Role::where('name', 'KETUA_RT')->first();
        $this->assertNotNull($ketuaRt);
        $this->assertTrue($ketuaRt->hasPermission('surat.verify.rt'));
        $this->assertFalse($ketuaRt->hasPermission('surat.verify.rw'));

        $warga = Role::where('name', 'WARGA')->first();
        $this->assertNotNull($warga);
        $this->assertTrue($warga->hasPermission('surat.create'));
        $this->assertFalse($warga->hasPermission('keuangan.approve'));
    }

    /**
     * Test User model helper methods and audit log creation.
     */
    public function test_user_model_rbac_and_audit_log(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $rtRole = Role::where('name', 'KETUA_RT')->first();

        $user = User::create([
            'role_id' => $rtRole->id,
            'username' => 'rt001',
            'full_name' => 'Bambang Ketua RT',
            'email' => 'rt001@rw047.id',
            'password' => 'secret123',
            'rt_code' => '001',
            'status' => 'ACTIVE',
        ]);

        $this->assertTrue($user->hasRole('KETUA_RT'));
        $this->assertFalse($user->hasRole('BENDAHARA_RW'));
        $this->assertTrue($user->hasPermission('surat.verify.rt'));
        $this->assertFalse($user->hasPermission('surat.verify.rw'));

        // Test Audit Log
        $audit = AuditLog::create([
            'user_id' => $user->id,
            'module' => 'Persuratan',
            'action' => 'VERIFY',
            'entity_type' => 'PengajuanSurat',
            'entity_id' => '1',
            'old_values' => ['status' => 'SUBMITTED'],
            'new_values' => ['status' => 'RT_REVIEW'],
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $audit->id,
            'module' => 'Persuratan',
            'action' => 'VERIFY',
            'entity_type' => 'PengajuanSurat',
        ]);

        $this->assertEquals($user->id, $audit->user->id);
    }
}
