<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'WARGA',
                'display_name' => 'Warga',
                'description' => 'Akses layanan publik dan mandiri (self-service) bagi warga RW 047.',
            ],
            [
                'name' => 'KETUA_RT',
                'display_name' => 'Ketua RT',
                'description' => 'Pengelolaan data warga dan verifikasi awal pengajuan surat tingkat RT.',
            ],
            [
                'name' => 'SEKRETARIS_RW',
                'display_name' => 'Sekretaris RW',
                'description' => 'Pengelolaan administrasi kependudukan, informasi publik, dan verifikasi persuratan RW.',
            ],
            [
                'name' => 'BENDAHARA_RW',
                'display_name' => 'Bendahara RW',
                'description' => 'Pengelolaan keuangan dan validasi catatan iuran kas RW.',
            ],
            [
                'name' => 'KETUA_RW',
                'display_name' => 'Ketua RW',
                'description' => 'Persetujuan akhir persuratan, monitoring operasional, dan pengawasan layanan RW.',
            ],
            [
                'name' => 'SUPER_ADMIN',
                'display_name' => 'Super Admin',
                'description' => 'Akses penuh administrasi teknis sistem, manajemen akun pengguna, dan audit aktivitas.',
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['name' => $roleData['name']],
                [
                    'display_name' => $roleData['display_name'],
                    'description' => $roleData['description'],
                ]
            );
        }
    }
}
