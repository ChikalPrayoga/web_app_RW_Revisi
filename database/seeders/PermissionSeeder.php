<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Modul: User Management / Auth
            ['name' => 'users.view', 'module' => 'User Management'],
            ['name' => 'users.create', 'module' => 'User Management'],
            ['name' => 'users.edit', 'module' => 'User Management'],
            ['name' => 'users.delete', 'module' => 'User Management'],

            // Modul: Kependudukan
            ['name' => 'kependudukan.view', 'module' => 'Kependudukan'],
            ['name' => 'kependudukan.create', 'module' => 'Kependudukan'],
            ['name' => 'kependudukan.edit', 'module' => 'Kependudukan'],
            ['name' => 'kependudukan.delete', 'module' => 'Kependudukan'],
            ['name' => 'kependudukan.verify', 'module' => 'Kependudukan'],

            // Modul: Persuratan
            ['name' => 'surat.view', 'module' => 'Persuratan'],
            ['name' => 'surat.create', 'module' => 'Persuratan'],
            ['name' => 'surat.verify.rt', 'module' => 'Persuratan'],
            ['name' => 'surat.verify.rw', 'module' => 'Persuratan'],

            // Modul: Laporan & Aspirasi
            ['name' => 'laporan.view', 'module' => 'Laporan & Aspirasi'],
            ['name' => 'laporan.create', 'module' => 'Laporan & Aspirasi'],
            ['name' => 'laporan.update_status', 'module' => 'Laporan & Aspirasi'],
            ['name' => 'laporan.delete', 'module' => 'Laporan & Aspirasi'],

            // Modul: Keuangan
            ['name' => 'keuangan.view', 'module' => 'Keuangan'],
            ['name' => 'keuangan.record', 'module' => 'Keuangan'],
            ['name' => 'keuangan.approve', 'module' => 'Keuangan'],
            ['name' => 'keuangan.manage_types', 'module' => 'Keuangan'],

            // Modul: Informasi Publik
            ['name' => 'informasi.view', 'module' => 'Informasi Publik'],
            ['name' => 'informasi.create', 'module' => 'Informasi Publik'],
            ['name' => 'informasi.edit', 'module' => 'Informasi Publik'],
            ['name' => 'informasi.delete', 'module' => 'Informasi Publik'],

            // Modul: Dashboard
            ['name' => 'dashboard.view', 'module' => 'Dashboard'],

            // Modul: Audit
            ['name' => 'audit.view', 'module' => 'Audit'],
        ];

        $permissionModels = [];
        foreach ($permissions as $perm) {
            $permissionModels[$perm['name']] = Permission::updateOrCreate(
                ['name' => $perm['name']],
                ['module' => $perm['module']]
            );
        }

        // Matriks Hak Akses per Peran sesuai PRD Bagian 2
        $rolePermissions = [
            'SUPER_ADMIN' => array_keys($permissionModels),

            'KETUA_RW' => [
                'dashboard.view',
                'users.view',
                'kependudukan.view',
                'surat.view',
                'surat.verify.rw',
                'laporan.view',
                'laporan.update_status',
                'keuangan.view',
                'keuangan.approve',
                'informasi.view',
                'informasi.create',
                'informasi.edit',
                'informasi.delete',
                'audit.view',
            ],

            'SEKRETARIS_RW' => [
                'dashboard.view',
                'kependudukan.view',
                'kependudukan.create',
                'kependudukan.edit',
                'kependudukan.verify',
                'surat.view',
                'surat.verify.rw',
                'laporan.view',
                'laporan.update_status',
                'informasi.view',
                'informasi.create',
                'informasi.edit',
                'informasi.delete',
            ],

            'BENDAHARA_RW' => [
                'dashboard.view',
                'keuangan.view',
                'keuangan.record',
                'keuangan.approve',
                'keuangan.manage_types',
                'informasi.view',
            ],

            'KETUA_RT' => [
                'dashboard.view',
                'kependudukan.view',
                'kependudukan.create',
                'kependudukan.edit',
                'surat.view',
                'surat.verify.rt',
                'laporan.view',
                'laporan.update_status',
                'keuangan.view',
                'keuangan.record',
                'informasi.view',
            ],

            'WARGA' => [
                'surat.create',
                'surat.view',
                'laporan.create',
                'laporan.view',
                'informasi.view',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $permissionIds = collect($perms)
                    ->map(fn (string $name) => $permissionModels[$name]->id ?? null)
                    ->filter()
                    ->all();

                $role->permissions()->sync($permissionIds);
            }
        }
    }
}
