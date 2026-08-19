<?php

declare(strict_types=1);

namespace App\Support\Security;

use Illuminate\Support\Facades\Crypt;

class DataEncryptionService
{
    /**
     * Enkripsi nilai plaintext menggunakan AES-256-CBC dengan random IV (Laravel Crypt).
     */
    public static function encrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return Crypt::encryptString($value);
    }

    /**
     * Dekripsi ciphertext AES-256-CBC menjadi plaintext.
     */
    public static function decrypt(?string $payload): ?string
    {
        if ($payload === null || $payload === '') {
            return $payload;
        }

        try {
            return Crypt::decryptString($payload);
        } catch (\Throwable $e) {
            // Kembalikan payload asli jika gagal dekripsi (mis. saat testing/mock)
            return $payload;
        }
    }

    /**
     * Menghasilkan hash deterministik HMAC-SHA256 untuk pencarian exact-match & penegakan uniqueness.
     * Menggunakan DATA_SEARCH_HASH_KEY yang terpisah dari APP_KEY.
     * Fail-fast: dilarang fallback ke APP_KEY atau hardcoded secret.
     */
    public static function deterministicHash(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $key = config('hashing.data_search_hash_key');
        if (empty($key) || ! is_string($key)) {
            throw new \RuntimeException('DATA_SEARCH_HASH_KEY is not configured. Hashing cannot proceed.');
        }

        return hash_hmac('sha256', (string) $value, $key);
    }
}
