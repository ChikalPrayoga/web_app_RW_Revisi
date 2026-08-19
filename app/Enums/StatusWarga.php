<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusWarga: string
{
    case TETAP = 'TETAP';
    case KONTRAK = 'KONTRAK';
    case PINDAH = 'PINDAH';
    case MENINGGAL = 'MENINGGAL';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
