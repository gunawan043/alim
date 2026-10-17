<?php

namespace App\Domain\Events;

use App\Models\DormitoryPermit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BoardingPermitDecided
{
    use Dispatchable;
    use SerializesModels;

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public function __construct(
        public readonly DormitoryPermit $permit,
        public readonly string $decision,
        public readonly ?string $decidedBy = null,
        public readonly ?string $note = null,
    ) {}
}
