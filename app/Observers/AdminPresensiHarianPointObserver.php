<?php

namespace App\Observers;

use App\Models\AdminPresensiHarian;
use App\Services\Dormitory\DormitoryViolationAutomationService;

class AdminPresensiHarianPointObserver
{
    public function __construct(private readonly DormitoryViolationAutomationService $automation) {}

    public function saved(AdminPresensiHarian $record): void
    {
        $this->automation->syncAcademicAttendance($record);
    }

    public function deleted(AdminPresensiHarian $record): void
    {
        $this->automation->clearSource(DormitoryViolationAutomationService::SOURCE_ACADEMIC_ATTENDANCE_ALPA, $record->id);
    }
}
