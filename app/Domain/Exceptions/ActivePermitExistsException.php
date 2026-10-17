<?php

namespace App\Domain\Exceptions;

use Exception;

class ActivePermitExistsException extends Exception
{
    public function __construct(
        string $message = 'Santri masih memiliki izin yang belum selesai.',
        ?array $details = null,
        int $code = 0
    ) {
        parent::__construct($message, $code);
        $this->details = $details ?? [];
    }

    /** @var array<string, mixed> */
    public array $details;
}
