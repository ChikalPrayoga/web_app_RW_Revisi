<?php

declare(strict_types=1);

namespace App\Modules\Auth\Exceptions;

use Exception;

/**
 * Dilempar saat user yang mencoba login ditemukan tapi berstatus INACTIVE.
 * Dibedakan dari InvalidCredentialsException agar Controller dapat memberi
 * respons HTTP yang tepat (tetap 422 per kontrak API — lihat catatan di
 * AuthController::login()).
 */
class AccountInactiveException extends Exception
{
    public function __construct()
    {
        parent::__construct('Akun Anda telah dinonaktifkan. Hubungi administrator untuk bantuan.');
    }
}
