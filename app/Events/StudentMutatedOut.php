<?php

namespace App\Events;

use App\Models\Student;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentMutatedOut
{
    use Dispatchable, SerializesModels;

    public const TYPE_GRADUATION = 'graduation';

    public const TYPE_DROPOUT = 'dropout';

    public const TYPE_MUTATION = 'mutation';

    public function __construct(
        public readonly Student $student,
        public readonly ?object $mutation,
        public readonly string $outType,
        public readonly ?string $leaveDate = null,
        public readonly ?string $actorId = null,
    ) {}
}
