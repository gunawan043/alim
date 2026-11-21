<?php

namespace App\Exceptions;

use RuntimeException;

class ApprovalCommandException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 422,
        public readonly string $errorCode = 'approval_command_error',
        public readonly array $context = []
    ) {
        parent::__construct($message);
    }

    public static function forbidden(string $message, string $code = 'actor_not_authorized'): self
    {
        return new self($message, 403, $code);
    }

    public static function conflict(string $message, string $code, array $context = []): self
    {
        return new self($message, 409, $code, $context);
    }

    public static function invalid(string $message, string $code, array $context = []): self
    {
        return new self($message, 422, $code, $context);
    }
}
