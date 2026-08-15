<?php

declare(strict_types=1);

namespace App\Modules\Auth\Exceptions;

use Exception;

/**
 * Dilempar saat kredensial login (email/password) tidak cocok.
 * Pesan dibuat generik untuk mencegah enumeration attack (tidak membocorkan
 * apakah email terdaftar atau password-nya yang salah).
 *
 * @see SYSTEM_ARCHITECTURE.md §4.2
 */
class InvalidCredentialsException extends Exception
{
    public function __construct()
    {
        parent::__construct('Email atau kata sandi yang Anda masukkan salah.');
    }
}
