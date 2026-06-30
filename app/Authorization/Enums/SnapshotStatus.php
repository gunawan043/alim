<?php

declare(strict_types=1);

namespace App\Authorization\Enums;

enum SnapshotStatus: string
{
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';
    case FAILED = 'failed';
}