<?php

namespace App\Events;

use App\Models\GtkProfile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GtkProfileUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public GtkProfile $gtkProfile,
        public string $changeType = 'updated',
        public ?string $schoolId = null,
        public ?string $academicYearId = null,
    ) {}
}
