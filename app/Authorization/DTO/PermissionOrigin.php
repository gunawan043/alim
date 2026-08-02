<?php

declare(strict_types=1);

namespace App\Authorization\DTO;

use App\Authorization\Enums\PermissionSource;
use App\Authorization\ValueObjects\ScopeKey;

final readonly class PermissionOrigin
{
    public function __construct(
        public string $provider,
        public string $permission,
        public string $reason,
        public ScopeKey $scope,
        public PermissionSource $source = PermissionSource::EMPLOYMENT,
    ) {}
}
