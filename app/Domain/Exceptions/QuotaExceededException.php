<?php

namespace App\Domain\Exceptions;

use Exception;

class QuotaExceededException extends Exception
{
    public function __construct(
        string $message = 'Kuota telah mencapai batas maksimum.',
        ?array $details = null,
        int $code = 0
    ) {
        parent::__construct($message, $code);
        $this->details = $details ?? [];
    }

    /** @var array<string, mixed> */
    public array $details;
}
