<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidStateTransitionException extends RuntimeException
{
    public function __construct(
        string $fromState,
        string $toState,
        ?string $entityType = null,
        ?int $entityId = null
    ) {
        $message = "Invalid state transition: '{$fromState}' -> '{$toState}'";
        if ($entityType && $entityId !== null) {
            $message .= " ({$entityType}:{$entityId})";
        }

        parent::__construct($message, 422);
    }
}
