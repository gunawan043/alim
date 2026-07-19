<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\Kaldik;
use App\Models\WaliSantri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KalenderController extends Controller
{
    // ── GET /api/mobile/v1/kalender ────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId');

        $month = $request->query('month');
        $year = $request->query('year');

        $studentIds = WaliSantri::where('user_id', $user->id)
            ->active()
            ->pluck('student_id');

        // Dormitory ids not used by Kaldik — removed dead query
        // $dormitoryIds = ... (dead code removed)

        $query = Kaldik::with(['workUnit:id,name'])
            ->where('is_active', true);

        // Filter by academic year with school context
        $academicYearId = \App\Models\AcademicYear::where(function ($q) use ($schoolId) {
                $q->where('is_active', true);
                if ($schoolId) {
                    $q->orWhereHas('workUnit.school', fn ($sq) => $sq->where('id', $schoolId));
                }
            })
            ->value('id');

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        if ($month && $year) {
            $startDate = "$year-$month-01";
            $endDate = date('Y-m-t', strtotime($startDate));
            $query->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            });
        }

        $records = $query->orderBy('start_date')->get()->map(fn ($k) => [
            'id' => $k->id,
            'name' => $k->name,
            'category' => $k->category,
            'category_label' => ($k->category === Kaldik::CATEGORY_AGENDA) ? 'Agenda Kegiatan' : 'Kaldik',
            'type' => $k->type,
            'type_label' => match ($k->type) {
                'tahunan' => 'Tahunan',
                'mid_semester' => 'Mid Semester',
                default => ucfirst($k->type ?? ''),
            },
            'color' => $k->color,
            'start_date' => $k->start_date?->toDateString(),
            'end_date' => $k->end_date?->toDateString(),
            'description' => $k->description,
            'work_unit' => $k->workUnit ? ['id' => $k->workUnit->id, 'name' => $k->workUnit->name] : null,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'kalender' => $records,
                'total' => $records->count(),
            ],
        ]);
    }
}