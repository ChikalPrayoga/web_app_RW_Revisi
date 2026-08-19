<?php

declare(strict_types=1);

namespace App\Support\Security;

class PiiMaskingHelper
{
    /**
     * Masking Nomor NIK (16 digit) sesuai formula resmi:
     * first 4 + 8 'x' + last 4 (contoh: 3216xxxxxxxx0021).
     */
    public static function maskNik(?string $nik): ?string
    {
        if ($nik === null || $nik === '') {
            return null;
        }

        $length = strlen($nik);
        if ($length < 8) {
            return str_repeat('x', $length);
        }

        if ($length === 16) {
            return substr($nik, 0, 4).'xxxxxxxx'.substr($nik, 12, 4);
        }

        // Generic fallback for non-standard length
        $prefix = substr($nik, 0, 4);
        $suffix = substr($nik, -4);
        $middleCount = max(0, $length - 8);

        return $prefix.str_repeat('x', $middleCount).$suffix;
    }

    /**
     * Masking Nomor Kartu Keluarga (16 digit) sesuai formula resmi:
     * first 4 + 8 'x' + last 4 (contoh: 3216xxxxxxxx0012).
     */
    public static function maskNoKk(?string $noKk): ?string
    {
        return self::maskNik($noKk);
    }

    /**
     * Masking alamat domisili untuk daftar ringkas API.
     */
    public static function maskAddress(?string $address): ?string
    {
        if ($address === null || $address === '') {
            return null;
        }

        return $address;
    }
}
