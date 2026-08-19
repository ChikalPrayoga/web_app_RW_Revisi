<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status verifikasi pencatatan iuran warga oleh Bendahara RW.
 *
 * Sesuai DATABASE_SCHEMA.md §3.10 dan API_SPECIFICATION.md §3.6.
 * Urutan alur: PENDING → APPROVED
 *                      ↘ REJECTED (oleh Bendahara RW)
 *
 * @see DATABASE_SCHEMA.md §3.10 kolom status
 * @see API_SPECIFICATION.md §3.6
 */
enum StatusCatatanIuran: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Status yang dianggap final (tidak dapat diubah kembali).
     *
     * @return array<int, self>
     */
    public static function finalStatuses(): array
    {
        return [self::APPROVED, self::REJECTED];
    }

    /**
     * Apakah status transaksi ini sudah final.
     */
    public function isFinal(): bool
    {
        return in_array($this, self::finalStatuses(), true);
    }
}
