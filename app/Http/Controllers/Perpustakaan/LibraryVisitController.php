<?php

namespace App\Http\Controllers\Perpustakaan;

use App\Http\Controllers\Controller;
use App\Models\LibraryMember;
use App\Models\LibraryUnit;
use App\Models\LibraryVisit;
use App\Models\StudentClassHistory;
use App\Services\LibraryAccess;
use Illuminate\Http\Request;

class LibraryVisitController extends Controller
{
    public function __construct(private readonly LibraryAccess $access) {}

    public function index(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $units = $this->access->unitsFor($request->user());
        $unit = $units->firstWhere('id', $request->input('unit_id')) ?? $units->first();
        abort_unless($unit, 404, 'Belum ada unit perpustakaan.');
        $members = LibraryMember::with(['student:id,name,nis', 'user:id,name'])->where('library_unit_id', $unit->id)->where('status', LibraryMember::STATUS_ACTIVE)->orderBy('member_number')->get();
        $visits = LibraryVisit::with(['member.student:id,name,nis', 'member.user:id,name', 'officer:id,name'])
            ->where('library_unit_id', $unit->id)
            ->when($request->filled('date'), fn ($q) => $q->whereDate('visit_date', $request->date))
            ->latest()->limit(200)->get();
        $stats = [
            'today' => LibraryVisit::where('library_unit_id', $unit->id)->whereDate('visit_date', today())->count(),
            'month' => LibraryVisit::where('library_unit_id', $unit->id)->whereMonth('visit_date', now()->month)->whereYear('visit_date', now()->year)->count(),
        ];

        return view('perpustakaan.visits.index', compact('userId', 'units', 'unit', 'members', 'visits', 'stats'));
    }

    public function store(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $validated = $request->validate([
            'library_unit_id' => 'required|exists:library_units,id',
            'library_member_id' => 'nullable|exists:library_members,id',
            'visitor_name' => 'nullable|string|max:191',
            'visitor_type' => 'required|in:student,gtk,guest',
            'purpose' => 'nullable|string|max:191',
            'visit_date' => 'required|date',
            'notes' => 'nullable|string|max:2000',
        ]);
        $unit = LibraryUnit::findOrFail($validated['library_unit_id']);
        abort_unless($this->access->canAccessUnit($request->user(), $unit), 403);
        $member = $validated['library_member_id'] ? LibraryMember::with('student')->findOrFail($validated['library_member_id']) : null;
        abort_if($member && $member->library_unit_id !== $unit->id, 422, 'Anggota bukan milik unit ini.');
        $name = $member?->display_name ?: ($validated['visitor_name'] ?? null);
        abort_if(blank($name), 422, 'Nama pengunjung wajib diisi.');
        $class = $member?->student
            ? StudentClassHistory::with('studyGroup')->where('student_id', $member->student_id)->latest()->first()?->studyGroup?->name
            : null;

        LibraryVisit::create([
            'library_unit_id' => $unit->id,
            'library_member_id' => $member?->id,
            'visitor_name' => $name,
            'visitor_type' => $validated['visitor_type'],
            'class_snapshot' => $class,
            'visit_date' => $validated['visit_date'],
            'purpose' => $validated['purpose'] ?? null,
            'officer_id' => $request->user()->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Kunjungan dicatat.');
    }
}
