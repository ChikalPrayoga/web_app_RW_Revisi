<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Jenis surat yang dapat diajukan warga.
 *
 * Sesuai DATABASE_SCHEMA.md §3.7 kolom jenis_surat dan API_SPECIFICATION.md §3.4.1.
 *
 * @see DATABASE_SCHEMA.md §3.7 kolom jenis_surat
 */
enum JenisSurat: string
{
    case SURAT_PENGANTAR = 'SURAT_PENGANTAR';
    case SURAT_KETERANGAN_DOMISILI = 'SURAT_KETERANGAN_DOMISILI';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::SURAT_PENGANTAR => 'Surat Pengantar',
            self::SURAT_KETERANGAN_DOMISILI => 'Surat Keterangan Domisili',
        };
    }
}
