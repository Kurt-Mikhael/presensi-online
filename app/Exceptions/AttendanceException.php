<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilempar ketika proses presensi gagal pada validasi sisi server.
 * Secara otomatis diubah menjadi JSON dengan error_code oleh handler exception bootstrap.
 */
class AttendanceException extends RuntimeException
{
    public function __construct(
        public string $errorCode,
        string $message,
        public int $httpStatus = 422,
    ) {
        parent::__construct($message);
    }
}