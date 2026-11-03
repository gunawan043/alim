<?php

namespace App\Http\Controllers\Dormitory;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryMealConsumption;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Konsumsi Asrama — pencatatan porsi makan per asrama, tanggal, dan jenis makan.
 */
class MealConsumptionController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $activeYear = AcademicYear::where('is_active', true)->first();

        $date = Carbon::parse($request->input('date') ?: now()->toDateString())->toDateString();

        $dormitories = Dormitory::query()
            ->where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get(['id', 'name']);

        $dormitoryId = $request->input('dormitory_id') ?: $dormitories->first()?->id;

        $records = DormitoryMealConsumption::query()
            ->with('dormitory:id,name')
            ->whereDate('consumption_date', $date)
            ->when($dormitoryId, fn ($q) => $q->where('dormitory_id', $dormitoryId))
            ->orderBy('meal_type')
            ->get()
            ->keyBy('meal_type');

        $summary = [
            'students' => $records->sum(fn ($r) => (int) $r->students_count),
            'guests' => $records->sum(fn ($r) => (int) $r->guests_count),
            'portions' => $records->sum(fn ($r) => $r->totalPortions()),
        ];

        $history = DormitoryMealConsumption::query()
            ->with('dormitory:id,name')
            ->when($dormitoryId, fn ($q) => $q->where('dormitory_id', $dormitoryId))
            ->whereDate('consumption_date', '<', $date)
            ->orderByDesc('consumption_date')
            ->limit(10)
            ->get();

        return view('dormitory.meals.index', [
            'userId' => $userId,
            'date' => $date,
            'dormitories' => $dormitories,
            'dormitoryId' => $dormitoryId,
            'records' => $records,
            'summary' => $summary,
            'history' => $history,
            'mealTypes' => DormitoryMealConsumption::MEAL_TYPES,
            'activeYear' => $activeYear,
        ]);
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'dormitory_id' => 'required|exists:dormitories,id',
            'consumption_date' => 'required|date',
            'meal_type' => 'required|in:'.implode(',', array_keys(DormitoryMealConsumption::MEAL_TYPES)),
            'students_count' => 'required|integer|min:0|max:5000',
            'guests_count' => 'nullable|integer|min:0|max:5000',
            'notes' => 'nullable|string|max:255',
        ]);

        $activeYear = AcademicYear::where('is_active', true)->first();

        DormitoryMealConsumption::updateOrCreate(
            [
                'dormitory_id' => $validated['dormitory_id'],
                'consumption_date' => $validated['consumption_date'],
                'meal_type' => $validated['meal_type'],
            ],
            [
                'academic_year_id' => $activeYear?->id,
                'students_count' => $validated['students_count'],
                'guests_count' => $validated['guests_count'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'recorded_by' => $userId,
            ]
        );

        return redirect()
            ->route('user.meals.index', [
                'userId' => $userId,
                'date' => $validated['consumption_date'],
                'dormitory_id' => $validated['dormitory_id'],
            ])
            ->with('success', 'Data konsumsi tersimpan.');
    }

    public function destroy(Request $request, string $userId, string $mealId)
    {
        DormitoryMealConsumption::findOrFail($mealId)->delete();

        return redirect()
            ->route('user.meals.index', ['userId' => $userId])
            ->with('success', 'Data konsumsi dihapus.');
    }
}
