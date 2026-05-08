<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    /**
     * Display a listing of academic years (centralized — managed by admin).
     */
    public function index(Request $request)
    {
        $query = AcademicYear::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $academicYears = $query->orderBy('name', 'desc')->orderBy('semester')->paginate(15)->withQueryString();

        return view('academic-years.index', compact('academicYears'));
    }

    /**
     * Show the form for creating a new academic year.
     */
    public function create()
    {
        return view('academic-years.create');
    }

    /**
     * Store a newly created academic year.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:50|unique:academic_years,name',
            'semester'           => 'required|in:ganjil,genap|unique:academic_years,semester,name',
            'is_active'          => 'boolean',
            'start_date'         => 'nullable|date',
            'end_date'           => 'nullable|date|after_or_equal:start_date',
            'registration_start' => 'nullable|date',
            'registration_end'   => 'nullable|date|after_or_equal:registration_start',
        ]);

        // If set as active, deactivate all others
        if (!empty($data['is_active'])) {
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
        }

        $academicYear = AcademicYear::create($data);

        return redirect()->route('academic-years.show', $academicYear->id)
            ->with('success', 'Tahun ajaran berhasil disimpan.');
    }

    /**
     * Display the specified academic year.
     */
    public function show(string $id)
    {
        $academicYear = AcademicYear::findOrFail($id);
        return view('academic-years.show', compact('academicYear'));
    }

    /**
     * Show the form for editing the specified academic year.
     */
    public function edit(string $id)
    {
        $academicYear = AcademicYear::findOrFail($id);
        return view('academic-years.edit', compact('academicYear'));
    }

    /**
     * Update the specified academic year.
     */
    public function update(Request $request, string $id)
    {
        $academicYear = AcademicYear::findOrFail($id);

        $data = $request->validate([
            'name'                => 'required|string|max:50|unique:academic_years,name,' . $id,
            'semester'           => 'required|in:ganjil,genap|unique:academic_years,semester,' . $id . ',id',
            'is_active'          => 'boolean',
            'start_date'         => 'nullable|date',
            'end_date'           => 'nullable|date|after_or_equal:start_date',
            'registration_start' => 'nullable|date',
            'registration_end'   => 'nullable|date|after_or_equal:registration_start',
        ]);

        // If set as active, deactivate all others
        if (!empty($data['is_active'])) {
            AcademicYear::where('is_active', true)->where('id', '!=', $id)->update(['is_active' => false]);
        }

        $academicYear->update($data);

        return redirect()->route('academic-years.show', $academicYear->id)
            ->with('success', 'Tahun ajaran berhasil diperbarui.');
    }

    /**
     * Remove the specified academic year.
     */
    public function destroy(string $id)
    {
        $academicYear = AcademicYear::findOrFail($id);
        $academicYear->delete();

        return redirect()->route('academic-years.index')
            ->with('success', 'Tahun ajaran berhasil dihapus.');
    }

    /**
     * Toggle the active status.
     */
    public function toggleActive(string $id)
    {
        $academicYear = AcademicYear::findOrFail($id);

        if (!$academicYear->is_active) {
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
        }

        $academicYear->update(['is_active' => !$academicYear->is_active]);

        $status = $academicYear->fresh()->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Tahun ajaran berhasil {$status}.");
    }
}
