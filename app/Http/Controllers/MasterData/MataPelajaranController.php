<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MataPelajaranController extends Controller
{
    /**
     * Index: list mata pelajaran, grouped by category
     * Akses: Admin TU / Waka / Kepsek
     */
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $subjects = Subject::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderByRaw("CASE
                WHEN category = 'nasional' THEN 1
                WHEN category = 'muatan_lokal' THEN 2
                WHEN category = 'pilihan' THEN 3
                ELSE 4 END")
            ->orderBy('name')
            ->get();

        $grouped = $subjects->groupBy(fn($s) => $s->category ?? 'lain')->map(fn($g, $k) => [
            'label'    => $k === 'nasional' ? 'Nasional' : ($k === 'muatan_lokal' ? 'Muatan Lokal' : ucfirst($k)),
            'items'    => $g,
            'count'    => $g->count(),
        ])->values();

        return view('master-data.mata-pelajaran.index', compact('userId', 'grouped'));
    }

    /**
     * Store: create / update subject
     */
    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'code'    => 'nullable|string|max:50',
            'category' => 'required|in:nasional,muatan_lokal,pilihan,lain',
            'credit_hours' => 'nullable|integer|min:1|max:10',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        Subject::updateOrCreate(
            ['id' => $request->id, 'school_id' => $schoolId],
            [
                'name'        => $validated['name'],
                'code'        => $validated['code'] ?? null,
                'category'    => $validated['category'],
                'credit_hours' => $validated['credit_hours'] ?? null,
                'description'  => $validated['description'] ?? null,
                'is_active'    => $request->boolean('is_active', true),
            ]
        );

        return redirect()->back()->with('success', 'Mata pelajaran berhasil disimpan.');
    }

    /**
     * Toggle is_active
     */
    public function toggle(Request $request, string $userId, string $subjectId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $subject = Subject::where('id', $subjectId)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->firstOrFail();

        $subject->update(['is_active' => !$subject->is_active]);

        return redirect()->back()->with('success', 'Status mata pelajaran berhasil diubah.');
    }
}
