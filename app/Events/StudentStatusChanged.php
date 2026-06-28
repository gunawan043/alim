<?php

namespace App\Events;

use App\Models\Student;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class StudentStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Student $student,
        public readonly array $payload = [],
    ) {}
}
