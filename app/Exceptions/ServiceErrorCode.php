<?php

namespace App\Exceptions;

use Exception;

class ServiceErrorCode extends Exception
{
    /** @var int HTTP status code */
    private int $status;

    /** @var array Additional details */
    private array $details;

    public function __construct(string $message, int $status = 500, array $details = [])
    {
        parent::__construct($message);
        $this->status = $status;
        $this->details = $details;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function getCodeAlias(): string
    {
        // Map short codes to HTTP-appropriate strings
        return match ($this->status) {
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            409 => 'CONFLICT',
            422 => 'UNPROCESSABLE_ENTITY',
            429 => 'TOO_MANY_REQUESTS',
            default => 'SERVER_ERROR',
        };
    }
}
