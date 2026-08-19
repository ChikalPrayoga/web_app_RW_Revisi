<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleName: string
{
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case KETUA_RW = 'KETUA_RW';
    case SEKRETARIS_RW = 'SEKRETARIS_RW';
    case BENDAHARA_RW = 'BENDAHARA_RW';
    case KETUA_RT = 'KETUA_RT';
    case WARGA = 'WARGA';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
