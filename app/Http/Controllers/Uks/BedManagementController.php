<?php

namespace App\Http\Controllers\Uks;

use App\Http\Controllers\Controller;
use App\Models\UksBed;
use Illuminate\Http\Request;

/**
 * Bed Management Controller — UKS bed assignment & occupancy tracking.
 */
class BedManagementController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $genderFilter = $request->input('gender');

        $query = UksBed::with(['currentAssignment.patient.student', 'dormitory'])
            ->when($genderFilter, fn ($q) => $q->where('gender', $genderFilter));

        if ($schoolId) {
            $query->whereHas('dormitory', fn ($d) => $d->where('school_id', $schoolId));
        }

        $beds = $query->orderBy('sort_order')->orderBy('building_or_room')
            ->orderBy('section')->orderBy('bed_number')->get();

        // Group by dormitory / building
        $groupedByDorm = $beds->groupBy(function ($bed) {
            return $bed->dormitory?->name ?? 'Tanpa Asrama';
        });

        $totalBeds = $beds->count();
        $occupiedBeds = $beds->where('is_occupied', true)->count();
        $availableBeds = $totalBeds - $occupiedBeds;

        return view('uks.beds.index', compact(
            'groupedByDorm',
            'totalBeds',
            'occupiedBeds',
            'availableBeds',
            'genderFilter'
        ));
    }

    public function create()
    {
        $dormitories = \App\Models\Dormitory::where('is_active', true)->get();

        return view('uks.beds.create', compact('dormitories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'dormitory_id' => 'nullable|uuid|exists:dormitories,id',
            'gender' => 'required|in:L,P',
            'building_or_room' => 'required|string|max:100',
            'section' => 'nullable|string|max:50',
            'bed_number' => 'required|string|max:20',
            'status' => 'required|in:tersedia,dipakai,perbaikan',
            'notes' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        UksBed::create($validated);

        return redirect()->route('user.uks.beds.index')
            ->with('success', 'Ranjang berhasil ditambahkan.');
    }

    public function show(string $uuid)
    {
        $bed = UksBed::with(['dormitory', 'currentAssignment.patient.student'])
            ->findOrFail($uuid);

        $assignmentHistory = $bed->assignments()
            ->with('patient.student')
            ->orderByDesc('assigned_at')
            ->take(20)
            ->get();

        return view('uks.beds.show', compact('bed', 'assignmentHistory'));
    }

    public function edit(string $uuid)
    {
        $bed = UksBed::findOrFail($uuid);
        $dormitories = \App\Models\Dormitory::where('is_active', true)->get();

        return view('uks.beds.edit', compact('bed', 'dormitories'));
    }

    public function update(Request $request, string $uuid)
    {
        $bed = UksBed::findOrFail($uuid);

        $validated = $request->validate([
            'dormitory_id' => 'nullable|uuid|exists:dormitories,id',
            'gender' => 'required|in:L,P',
            'building_or_room' => 'required|string|max:100',
            'section' => 'nullable|string|max:50',
            'bed_number' => 'required|string|max:20',
            'status' => 'required|in:tersedia,dipakai,perbaikan',
            'notes' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $bed->update($validated);

        return redirect()->route('user.uks.beds.show', $bed->id)
            ->with('success', 'Data ranjang berhasil diperbarui.');
    }

    public function destroy(string $uuid)
    {
        $bed = UksBed::findOrFail($uuid);

        // Cannot delete a bed with active assignments
        $hasActive = $bed->assignments()->where('status', 'assigned')->exists();
        if ($hasActive) {
            return back()->withErrors(['bed' => 'Tidak dapat menghapus ranjang yang masih memiliki pasien aktif.']);
        }

        $bed->delete();

        return redirect()->route('user.uks.beds.index')
            ->with('success', 'Ranjang berhasil dihapus.');
    }
}
