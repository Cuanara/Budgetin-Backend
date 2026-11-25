<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception khusus untuk error autentikasi yang pesannya aman ditampilkan ke user
 * (akun disuspend, email belum diverifikasi, dll).
 * Kode exception dipakai sebagai HTTP status code.
 */
class AuthException extends Exception
{
    public function __construct(string $message, int $statusCode = 403)
    {
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->getCode();
    }
}
