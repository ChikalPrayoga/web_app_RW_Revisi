<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status linimasa verifikasi pengajuan surat berjenjang.
 *
 * Sesuai DATABASE_SCHEMA.md §3.7 dan API_SPECIFICATION.md §3.4.
 * Urutan alur: SUBMITTED → RT_REVIEW → RW_REVIEW → COMPLETED
 *                                    ↘ REJECTED (oleh RT atau RW)
 *
 * @see DATABASE_SCHEMA.md §3.7 kolom current_status
 * @see API_SPECIFICATION.md §3.4.3 query parameter current_status
 */
enum StatusPengajuanSurat: string
{
    case SUBMITTED = 'SUBMITTED';
    case RT_REVIEW = 'RT_REVIEW';
    case RW_REVIEW = 'RW_REVIEW';
    case COMPLETED = 'COMPLETED';
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
     * Sesuai AGENTS.md §2.1: larangan mengubah status CLOSED/COMPLETED kembali.
     *
     * @return array<int, self>
     */
    public static function finalStatuses(): array
    {
        return [self::COMPLETED, self::REJECTED];
    }

    /**
     * Apakah status ini sudah final (tidak dapat diubah).
     */
    public function isFinal(): bool
    {
        return in_array($this, self::finalStatuses(), true);
    }
}
