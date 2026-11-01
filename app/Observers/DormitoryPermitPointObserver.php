<?php

namespace App\Observers;

use App\Models\DormitoryPermit;
use App\Services\Dormitory\DormitoryViolationAutomationService;

class DormitoryPermitPointObserver
{
    public function __construct(private readonly DormitoryViolationAutomationService $automation) {}

    public function saved(DormitoryPermit $permit): void
    {
        $this->automation->syncPermitReturn($permit);
    }

    public function deleted(DormitoryPermit $permit): void
    {
        $this->automation->clearSource(DormitoryViolationAutomationService::SOURCE_PERMIT_LATE_RETURN, $permit->id);
    }
}
