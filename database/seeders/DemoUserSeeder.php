<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder akun demo untuk simulasi & rehearsal presentasi (Local / Demo environment).
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = Hash::make('Password123!');

        $roles = Role::pluck('id', 'name');

        $users = [
            [
                'username' => 'superadmin',
                'email' => 'admin@rw047.id',
                'full_name' => 'Administrator RW 047',
                'role_id' => $roles[RoleName::SUPER_ADMIN->value] ?? null,
                'rt_code' => null,
                'status' => 'ACTIVE',
            ],
            [
                'username' => 'ketuarw',
                'email' => 'ketua.rw@rw047.id',
                'full_name' => 'H. Bambang Sutrisno (Ketua RW)',
                'role_id' => $roles[RoleName::KETUA_RW->value] ?? null,
                'rt_code' => null,
                'status' => 'ACTIVE',
            ],
            [
                'username' => 'sekretarisrw',
                'email' => 'sekretaris.rw@rw047.id',
                'full_name' => 'Siti Rahmawati (Sekretaris RW)',
                'role_id' => $roles[RoleName::SEKRETARIS_RW->value] ?? null,
                'rt_code' => null,
                'status' => 'ACTIVE',
            ],
            [
                'username' => 'bendahararw',
                'email' => 'bendahara.rw@rw047.id',
                'full_name' => 'Ahmad Hidayat (Bendahara RW)',
                'role_id' => $roles[RoleName::BENDAHARA_RW->value] ?? null,
                'rt_code' => null,
                'status' => 'ACTIVE',
            ],
            [
                'username' => 'ketuart01',
                'email' => 'ketua.rt01@rw047.id',
                'full_name' => 'Budi Santoso (Ketua RT 001)',
                'role_id' => $roles[RoleName::KETUA_RT->value] ?? null,
                'rt_code' => '001',
                'status' => 'ACTIVE',
            ],
            [
                'username' => 'ketuart02',
                'email' => 'ketua.rt02@rw047.id',
                'full_name' => 'Joko Widodo (Ketua RT 002)',
                'role_id' => $roles[RoleName::KETUA_RT->value] ?? null,
                'rt_code' => '002',
                'status' => 'ACTIVE',
            ],
            [
                'username' => 'warga01',
                'email' => 'warga@rw047.id',
                'full_name' => 'Dedi Setiawan (Warga)',
                'role_id' => $roles[RoleName::WARGA->value] ?? null,
                'rt_code' => null,
                'status' => 'ACTIVE',
            ],
        ];

        foreach ($users as $userData) {
            if (! $userData['role_id']) {
                continue;
            }

            User::updateOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, [
                    'password' => $defaultPassword,
                ])
            );
        }
    }
}
