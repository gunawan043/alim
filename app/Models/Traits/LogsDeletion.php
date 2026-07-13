<?php

namespace App\Models\Traits;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Trait LogsDeletion.
 * Automatically log model deletion events using Spatie Activity Log.
 * Include in models that have soft deletes and need audit trails.
 */
trait LogsDeletion
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Model " . static::class . " {$eventName}");
    }
}
