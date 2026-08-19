<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum untuk jenis/kategori konten informasi publik RW 047.
 *
 * @see docs/DATABASE_SCHEMA.md §3.12
 */
enum KategoriInformasi: string
{
    case PENGUMUMAN = 'PENGUMUMAN';
    case BERITA = 'BERITA';
    case AGENDA = 'AGENDA';

    /**
     * Label manusia yang ramah pengguna.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENGUMUMAN => 'Pengumuman',
            self::BERITA => 'Berita',
            self::AGENDA => 'Agenda Kegiatan',
        };
    }

    /**
     * Warna badge Tailwind untuk kategori.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::PENGUMUMAN => 'bg-primary-light text-primary border-primary/30',
            self::BERITA => 'bg-secondary-light text-secondary border-secondary/30',
            self::AGENDA => 'bg-warning-light text-warning border-warning/30',
        };
    }
}
