<?php

namespace App\Observers;

use App\Events\GtkProfileUpdated;
use App\Models\GtkProfile;

class GtkProfileObserver
{
    /**
     * Only recompute when a new GTK enters the system or is removed.
     * In-place edits to name, NIK, address, etc. do not affect teaching
     * workload capacity, so we intentionally skip `updated` to avoid
     * flooding the queue with no-signal recalculations.
     */
    public function created(GtkProfile $profile): void
    {
        GtkProfileUpdated::dispatch($profile, 'created');
    }

    public function deleted(GtkProfile $profile): void
    {
        GtkProfileUpdated::dispatch($profile, 'deleted');
    }

    // NOTE: `updated` intentionally omitted — in-place profile edits
    // do not affect teaching capacity and should not trigger recalculation.
}
