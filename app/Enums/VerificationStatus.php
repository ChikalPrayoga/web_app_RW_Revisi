<?php

declare(strict_types=1);

namespace App\Enums;

enum VerificationStatus: string
{
    case MENUNGGU_VERIFIKASI = 'MENUNGGU_VERIFIKASI';
    case TERVERIFIKASI = 'TERVERIFIKASI';
    case DITOLAK = 'DITOLAK';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
