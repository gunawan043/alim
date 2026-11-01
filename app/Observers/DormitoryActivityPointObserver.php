<?php

namespace App\Observers;

use App\Models\DormitoryActivityAttendance;
use App\Services\Dormitory\DormitoryViolationAutomationService;

class DormitoryActivityPointObserver
{
    public function __construct(private readonly DormitoryViolationAutomationService $automation) {}

    public function saved(DormitoryActivityAttendance $record): void
    {
        $this->automation->syncActivityAttendance($record);
    }

    public function deleted(DormitoryActivityAttendance $record): void
    {
        $this->automation->clearSource(DormitoryViolationAutomationService::SOURCE_ACTIVITY_ALPA, $record->id);
    }
}
