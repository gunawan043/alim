<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\GtkAdditionalTask;
use App\Models\GtkWorkUnit;
use App\Models\StructuralAssignment;
use Illuminate\Http\Request;

abstract class SatuanPendidikanDashboardController extends Controller
{
    protected function resolveContext(Request $request): array
    {
        $user = auth()->user();
        $schoolId = $request->attributes->get('schoolContextId');
        $academicYear = AcademicYear::where('is_active', true)->first();

        $gtkWorkUnits = GtkWorkUnit::where('user_id', $user->id)
            ->whereHas('workUnit', fn ($q) => $q->where('is_active', true))
            ->with('workUnit.school')
            ->get();

        $primaryWorkUnit = $gtkWorkUnits->where('is_primary', true)->first()
            ?? $gtkWorkUnits->first();

        $structuralAssignments = StructuralAssignment::where('user_id', $user->id)
            ->active()
            ->with('position')
            ->get();

        $additionalTasks = GtkAdditionalTask::where('user_id', $user->id)
            ->where(function ($q) use ($schoolId) {
                $q->whereNull('work_unit_id')
                    ->orWhereHas('workUnit', fn ($wq) => $wq->where('school_id', $schoolId));
            })
            ->where(function ($q) {
                $q->whereNull('tst')->orWhere('tst', '>=', now()->toDateString());
            })
            ->with(['decree', 'workUnit'])
            ->get();

        return compact('user', 'schoolId', 'academicYear', 'gtkWorkUnits',
            'primaryWorkUnit', 'structuralAssignments', 'additionalTasks');
    }

    protected function statsCard(string $label, string $value, string $icon, string $color): string
    {
        return sprintf(
            '<div class="col-xl-3 col-md-6">'.
            '  <div class="card stat-card h-100" style="border-left-color: %s;">'.
            '    <div class="card-body py-3">'.
            '      <div class="d-flex align-items-center gap-3">'.
            '        <div class="stat-icon" style="background:%s-subtle; width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center;">'.
            '          <i class="%s text-%s fs-4"></i>'.
            '        </div>'.
            '        <div>'.
            '          <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:10px;">%s</p>'.
            '          <h2 class="fw-bold ff-secondary mb-0">%s</h2>'.
            '        </div>'.
            '      </div>'.
            '    </div>'.
            '  </div>'.
            '</div>',
            $color, $color, $icon, $color, $label, $value
        );
    }
}
