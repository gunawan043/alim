<?php

namespace App\Http\Controllers\Perpustakaan;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\LibrarySetting;
use App\Models\LibraryUnit;
use App\Models\School;
use App\Services\LibraryAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LibraryUnitController extends Controller
{
    public function __construct(private readonly LibraryAccess $access) {}

    public function index(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $units = $this->access->unitsFor($request->user());
        $year = AcademicYear::where('is_active', true)->first();
        $settings = LibrarySetting::whereIn('library_unit_id', $units->pluck('id'))
            ->when($year, fn ($q) => $q->where('academic_year_id', $year->id))
            ->get()->keyBy('library_unit_id');
        $schools = School::active()->orderBy('name')->get(['id', 'name']);

        return view('perpustakaan.units.index', compact('userId', 'units', 'year', 'settings', 'schools'));
    }

    public function store(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $validated = $request->validate([
            'school_id' => 'nullable|exists:schools,id',
            'code' => 'required|string|max:30|unique:library_units,code',
            'name' => 'required|string|max:191',
            'gender_scope' => 'required|in:L,P,mixed',
            'level_scope' => 'required|in:sd,smp,sd_smp',
            'location' => 'nullable|string|max:191',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        LibraryUnit::create($validated);

        return back()->with('success', 'Unit perpustakaan ditambahkan.');
    }

    public function update(Request $request, string $userId, string $id)
    {
        $unit = LibraryUnit::findOrFail($id);
        abort_unless($this->access->canManageUnit($request->user(), $unit), 403);
        $validated = $request->validate([
            'school_id' => 'nullable|exists:schools,id',
            'code' => ['required', 'string', 'max:30', Rule::unique('library_units', 'code')->ignore($unit->id)],
            'name' => 'required|string|max:191',
            'gender_scope' => 'required|in:L,P,mixed',
            'level_scope' => 'required|in:sd,smp,sd_smp',
            'location' => 'nullable|string|max:191',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        $unit->update($validated);

        return back()->with('success', 'Unit perpustakaan diperbarui.');
    }

    public function updateSettings(Request $request, string $userId, string $id)
    {
        $unit = LibraryUnit::findOrFail($id);
        abort_unless($this->access->canManageUnit($request->user(), $unit), 403);
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'loan_days' => 'required|integer|min:1|max:365',
            'max_active_loans' => 'required|integer|min:1|max:50',
            'allow_renewal' => 'boolean',
            'max_renewals' => 'nullable|integer|min:0|max:20',
            'renewal_days' => 'nullable|integer|min:1|max:365',
            'allow_fine' => 'boolean',
            'fine_per_day' => 'nullable|numeric|min:0',
            'max_fine' => 'nullable|numeric|min:0',
            'replacement_card_fee' => 'nullable|numeric|min:0',
        ]);
        $validated['allow_renewal'] = $request->boolean('allow_renewal');
        $validated['allow_fine'] = $request->boolean('allow_fine');
        $validated['max_renewals'] = $validated['max_renewals'] ?? 0;
        $validated['renewal_days'] = $validated['renewal_days'] ?? $validated['loan_days'];
        $validated['fine_per_day'] = $validated['fine_per_day'] ?? 0;
        $validated['replacement_card_fee'] = $validated['replacement_card_fee'] ?? 0;
        LibrarySetting::updateOrCreate(
            ['library_unit_id' => $unit->id, 'academic_year_id' => $validated['academic_year_id']],
            $validated
        );

        return back()->with('success', 'Pengaturan perpustakaan disimpan.');
    }
}
