<?php

namespace App\Http\Controllers\Perpustakaan;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\LibraryProgram;
use App\Models\LibraryUnit;
use App\Services\LibraryAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LibraryProgramController extends Controller
{
    private const TYPES = [
        'procurement' => ['label' => 'Pengadaan Buku', 'icon' => 'ri-shopping-bag-3-line', 'category' => 'Genre'],
        'donation' => ['label' => 'Sumbangan Buku', 'icon' => 'ri-gift-line', 'category' => 'Sumber'],
        'weeding' => ['label' => 'Penyiangan Koleksi', 'icon' => 'ri-delete-bin-6-line', 'category' => 'Alasan'],
        'literacy' => ['label' => 'Program Literasi', 'icon' => 'ri-lightbulb-line', 'category' => 'Jenis'],
    ];

    public function __construct(private readonly LibraryAccess $access) {}

    public function index(Request $request, string $userId, string $type)
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        abort_unless($this->access->canManage($request->user()), 403);
        $units = $this->access->unitsFor($request->user());
        $unit = $units->firstWhere('id', $request->input('unit_id')) ?? $units->first();
        abort_unless($unit, 404, 'Belum ada unit perpustakaan.');
        $meta = self::TYPES[$type];
        $year = AcademicYear::where('is_active', true)->first();
        $programs = LibraryProgram::with('creator:id,name')
            ->where('library_unit_id', $unit->id)->where('type', $type)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->latest('event_date')->latest()->limit(200)->get();
        $stats = [
            'total' => $programs->count(),
            'draft' => $programs->where('status', 'draft')->count(),
            'active' => $programs->whereIn('status', ['submitted', 'approved', 'active', 'ordered'])->count(),
            'done' => $programs->where('status', 'completed')->count(),
        ];

        return view('perpustakaan.programs.index', compact('userId', 'units', 'unit', 'year', 'type', 'meta', 'programs', 'stats'));
    }

    public function store(Request $request, string $userId, string $type)
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        abort_unless($this->access->canManage($request->user()), 403);
        $validated = $this->validateProgram($request, $type);
        $unit = LibraryUnit::findOrFail($validated['library_unit_id']);
        abort_unless($this->access->canAccessUnit($request->user(), $unit), 403);
        LibraryProgram::create(array_merge($validated, [
            'type' => $type,
            'created_by' => $request->user()->id,
            'academic_year_id' => $validated['academic_year_id'] ?? AcademicYear::where('is_active', true)->value('id'),
        ]));

        return back()->with('success', self::TYPES[$type]['label'].' ditambahkan.');
    }

    public function update(Request $request, string $userId, string $id)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $program = LibraryProgram::with('unit')->findOrFail($id);
        abort_unless($this->access->canAccessUnit($request->user(), $program->unit), 403);
        $validated = $this->validateProgram($request, $program->type);
        $program->update($validated);

        return back()->with('success', 'Data diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $program = LibraryProgram::with('unit')->findOrFail($id);
        abort_unless($this->access->canAccessUnit($request->user(), $program->unit), 403);
        $program->delete();

        return back()->with('success', 'Data dihapus dari daftar aktif.');
    }

    private function validateProgram(Request $request, string $type): array
    {
        return $request->validate([
            'library_unit_id' => 'required|exists:library_units,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:191',
            'status' => ['required', Rule::in(['draft', 'submitted', 'approved', 'ordered', 'active', 'completed', 'rejected', 'cancelled'])],
            'event_date' => 'nullable|date',
            'quantity' => 'nullable|integer|min:0',
            'amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:5000',
        ]);
    }
}
