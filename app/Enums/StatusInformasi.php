<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum untuk status publikasi konten informasi publik.
 *
 * @see docs/DATABASE_SCHEMA.md §3.12
 */
enum StatusInformasi: string
{
    case DRAFT = 'DRAFT';
    case PUBLISHED = 'PUBLISHED';
    case ARCHIVED = 'ARCHIVED';

    /**
     * Label manusia yang ramah pengguna.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Diterbitkan',
            self::ARCHIVED => 'Diarsipkan',
        };
    }

    /**
     * Warna badge Tailwind untuk status.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-background text-text-secondary border-border',
            self::PUBLISHED => 'bg-success-light text-success border-success/30',
            self::ARCHIVED => 'bg-danger-light text-danger border-danger/30',
        };
    }
}
