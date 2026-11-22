<?php

namespace App\Exceptions;

use RuntimeException;

class ConnectSsoException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 400,
        public readonly string $errorCode = 'connect_sso_error',
        public readonly array $context = []
    ) {
        parent::__construct($message);
    }

    public static function invalid(string $message, string $code, array $context = []): self
    {
        return new self($message, 422, $code, $context);
    }

    public static function forbidden(string $message, string $code, array $context = []): self
    {
        return new self($message, 403, $code, $context);
    }

    public static function notFound(string $message = 'Kode handoff tidak ditemukan.', string $code = 'code_not_found'): self
    {
        return new self($message, 404, $code);
    }

    public static function conflict(string $message, string $code, array $context = []): self
    {
        return new self($message, 409, $code, $context);
    }

    public static function gone(string $message = 'Kode handoff sudah kedaluwarsa.', string $code = 'code_expired'): self
    {
        return new self($message, 410, $code);
    }
}
