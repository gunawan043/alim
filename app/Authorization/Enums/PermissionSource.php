<?php

declare(strict_types=1);

namespace App\Authorization\Enums;

enum PermissionSource: string
{
    case EMPLOYMENT = 'employment';
    case ASSIGNMENT = 'assignment';
    case DELEGATION = 'delegation';
    case REVOCATION = 'revocation';
    case MANUAL = 'manual';
}