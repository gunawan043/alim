<?php

namespace App\Http\Controllers\Perpustakaan;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\LibraryItem;
use App\Models\LibraryStockOpname;
use App\Models\LibraryStockOpnameItem;
use App\Models\LibraryUnit;
use App\Services\LibraryAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LibraryStockOpnameController extends Controller
{
    public function __construct(private readonly LibraryAccess $access) {}

    public function index(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $units = $this->access->unitsFor($request->user());
        $unit = $units->firstWhere('id', $request->input('unit_id')) ?? $units->first();
        abort_unless($unit, 404, 'Belum ada unit perpustakaan.');
        $sessions = LibraryStockOpname::withCount(['items as checked_count' => fn ($q) => $q->where('finding', '!=', 'pending')])
            ->where('library_unit_id', $unit->id)->latest('session_date')->get();
        $session = $request->filled('session_id') ? $sessions->firstWhere('id', $request->session_id) : $sessions->first();
        $items = $session ? LibraryStockOpnameItem::with(['item.title:id,title,author,publisher,publication_year,genre_id', 'item.title.genre:id,name', 'checker:id,name'])
            ->where('library_stock_opname_id', $session->id)->get() : collect();
        $stats = [
            'items' => $items->count(),
            'checked' => $items->where('finding', '!=', 'pending')->count(),
            'missing' => $items->where('finding', 'missing')->count(),
            'moved' => $items->where('finding', 'moved')->count(),
        ];

        return view('perpustakaan.stock-opnames.index', compact('userId', 'units', 'unit', 'sessions', 'session', 'items', 'stats'));
    }

    public function store(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $validated = $request->validate([
            'library_unit_id' => 'required|exists:library_units,id',
            'session_date' => 'required|date',
            'notes' => 'nullable|string|max:2000',
        ]);
        $unit = LibraryUnit::findOrFail($validated['library_unit_id']);
        abort_unless($this->access->canAccessUnit($request->user(), $unit), 403);
        $year = AcademicYear::where('is_active', true)->first();

        $session = DB::transaction(function () use ($validated, $unit, $year, $request) {
            $session = LibraryStockOpname::create([
                'library_unit_id' => $unit->id,
                'academic_year_id' => $year?->id,
                'session_date' => $validated['session_date'],
                'status' => 'in_progress',
                'coordinator_id' => $request->user()->id,
                'notes' => $validated['notes'] ?? null,
                'started_at' => now(),
            ]);
            LibraryItem::where('library_unit_id', $unit->id)->where('status', '!=', LibraryItem::STATUS_WITHDRAWN)
                ->each(function ($item) use ($session) {
                    LibraryStockOpnameItem::create([
                        'library_stock_opname_id' => $session->id,
                        'library_item_id' => $item->id,
                        'finding' => 'pending',
                        'system_status' => $item->status,
                        'condition' => $item->condition,
                    ]);
                });

            return $session;
        });

        return redirect()->route('user.perpustakaan.stock-opnames.index', ['userId' => $userId, 'unit_id' => $unit->id, 'session_id' => $session->id])
            ->with('success', 'Sesi stock opname dimulai.');
    }

    public function updateItem(Request $request, string $userId, string $sessionId, string $itemId)
    {
        $row = LibraryStockOpnameItem::with('session.unit', 'item')->findOrFail($itemId);
        abort_unless($row->session?->library_unit_id === $row->item?->library_unit_id, 422, 'Data tidak konsisten.');
        abort_unless($this->access->canAccessUnit($request->user(), $row->session?->unit), 403);
        abort_if($row->session?->status === 'completed', 422, 'Sesi sudah selesai.');
        $validated = $request->validate([
            'finding' => 'required|in:found,borrowed,moved,missing,damaged,label_issue,number_issue',
            'physical_location' => 'nullable|string|max:191',
            'condition' => 'nullable|in:baik,rusak_ringan,rusak_sedang,rusak_berat,lost',
            'notes' => 'nullable|string|max:2000',
        ]);
        $row->update(array_merge($validated, ['checked_by' => $request->user()->id, 'checked_at' => now()]));
        if (in_array($validated['finding'], ['found', 'moved'], true) && ! empty($validated['physical_location'])) {
            $row->item?->update(['shelf_location' => $validated['physical_location']]);
        }
        if ($validated['finding'] === 'damaged' && ! empty($validated['condition'])) {
            $row->item?->update(['condition' => $validated['condition']]);
        }
        if ($validated['finding'] === 'missing') {
            $row->item?->update(['status' => LibraryItem::STATUS_LOST]);
        }

        return back()->with('success', 'Temuan stock opname disimpan.');
    }

    public function complete(Request $request, string $userId, string $id)
    {
        $session = LibraryStockOpname::findOrFail($id);
        abort_unless($this->access->canAccessUnit($request->user(), $session->unit), 403);
        $session->update(['status' => 'completed', 'completed_at' => now()]);

        return back()->with('success', 'Sesi stock opname diselesaikan.');
    }
}
