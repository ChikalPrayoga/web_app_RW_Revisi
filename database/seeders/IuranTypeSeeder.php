<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\IuranType;
use Illuminate\Database\Seeder;

class IuranTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Master data jenis iuran sesuai API_SPECIFICATION.md §3.6.1 dan DATABASE_SCHEMA.md §3.9.
     * Bersifat idempotent.
     */
    public function run(): void
    {
        $iuranTypes = [
            [
                'name' => 'Iuran Kebersihan & Keamanan',
                'code' => 'IKK',
                'default_amount' => 50000.00,
                'description' => 'Iuran bulanan kebersihan dan keamanan lingkungan RW 047',
                'is_active' => true,
            ],
            [
                'name' => 'Kas RW',
                'code' => 'KAS-RW',
                'default_amount' => 25000.00,
                'description' => 'Iuran kas operasional dan pemeliharaan lingkungan RW 047',
                'is_active' => true,
            ],
        ];

        foreach ($iuranTypes as $type) {
            IuranType::updateOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'default_amount' => $type['default_amount'],
                    'description' => $type['description'],
                    'is_active' => $type['is_active'],
                ]
            );
        }
    }
}
