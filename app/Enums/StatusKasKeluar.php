<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status verifikasi pengeluaran kas RW oleh Ketua RW (Dual-Control).
 *
 * Sesuai DATABASE_SCHEMA.md §3.11 dan API_SPECIFICATION.md §3.6.
 * Urutan alur: PENDING → APPROVED
 *                      ↘ REJECTED (oleh Ketua RW)
 *
 * @see DATABASE_SCHEMA.md §3.11 kolom status
 * @see API_SPECIFICATION.md §3.6.5-§3.6.7
 */
enum StatusKasKeluar: string
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
