<?php

declare(strict_types=1);

namespace App\Authorization\Contracts;

use App\Authorization\DTO\PermissionBag;
use App\Authorization\ValueObjects\OrganizationContext;
use Illuminate\Database\Eloquent\Model;

interface PermissionBuilder
{
    public function build(Model $user, OrganizationContext $context): PermissionBag;
}
