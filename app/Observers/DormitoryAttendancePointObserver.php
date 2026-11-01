<?php

namespace App\Observers;

use App\Models\DormitoryAttendance;
use App\Services\Dormitory\DormitoryViolationAutomationService;

class DormitoryAttendancePointObserver
{
    public function __construct(private readonly DormitoryViolationAutomationService $automation) {}

    public function saved(DormitoryAttendance $record): void
    {
        $this->automation->syncDormitoryAttendance($record);
    }

    public function deleted(DormitoryAttendance $record): void
    {
        $this->automation->clearSource(DormitoryViolationAutomationService::SOURCE_DORM_ATTENDANCE_ALPA, $record->id);
    }
}
