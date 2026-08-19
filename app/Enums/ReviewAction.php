<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Aksi verifikasi pada pengajuan surat oleh pengurus RT/RW.
 *
 * Sesuai API_SPECIFICATION.md §3.4.4 field `action`.
 *
 * @see API_SPECIFICATION.md §3.4.4 request body action
 */
enum ReviewAction: string
{
    case APPROVE = 'APPROVE';
    case REJECT = 'REJECT';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
