<?php

declare(strict_types=1);

namespace App\Authorization\Contracts;

use App\Authorization\ValueObjects\ScopeKey;

interface AuthorizationContext
{
    public function scopeKey(): ScopeKey;

    public function userId(): int|string;
}
