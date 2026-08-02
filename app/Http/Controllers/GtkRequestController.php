<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\GtkProfile;
use App\Models\GtkRequest;
use App\Models\GtkRequestItem;
use App\Models\WorkUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GtkRequestController extends Controller
{
    public function index(Request $request)
    {
        $userId = request()->route('userId');
        $query = GtkRequest::with(['workUnit', 'requestedBy', 'items'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('workUnit', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(15)->withQueryString();

        return view('gtk-requests.index', compact('requests', 'userId'));
    }

    public function create(Request $request)
    {
        $userId = request()->route('userId');
        $type = $request->get('type', GtkRequest::TYPE_PROCUREMENT);
        $workUnits = WorkUnit::orderBy('name')->get();
        $academicYears = AcademicYear::active()->orderByDesc('start_date')->get();
        $gtkProfiles = GtkProfile::with('user')->get();

        return view('gtk-requests.create', compact('type', 'workUnits', 'academicYears', 'gtkProfiles', 'userId'));
    }

    public function store(Request $request)
    {
        $type = $request->type;

        $baseRules = [
            'work_unit_id' => 'required|exists:work_units,id',
            'type' => 'required|in:procurement,trial,status_increase',
            'status' => 'in:draft,submitted',
        ];

        if ($type === GtkRequest::TYPE_PROCUREMENT) {
            $baseRules['academic_year_id'] = 'required|exists:academic_years,id';
            $baseRules['notes'] = 'nullable|string';
        }
        if ($type === GtkRequest::TYPE_TRIAL) {
            $baseRules['letter_number'] = 'nullable|string|max:100';
            $baseRules['letter_subject'] = 'nullable|string|max:255';
            $baseRules['letter_attachment'] = 'nullable|string|max:100';
            $baseRules['established_city'] = 'nullable|string|max:100';
            $baseRules['established_date'] = 'nullable|date';
        }

        $validated = $request->validate($baseRules);

        $gtkRequest = GtkRequest::create([
            ...$validated,
            'requested_by' => Auth::id(),
            'status' => 'draft',
        ]);

        // Save items based on type
        $this->saveItems($gtkRequest, $type, $request);

        return redirect()->route('gtk-requests.show', $gtkRequest->id)
            ->with('success', 'Request GTK berhasil dibuat.');
    }

    public function show(Request $request, string $requestUuid)
    {
        $userId = $request->route('userId');
        $gtkRequest = GtkRequest::with(['workUnit', 'academicYear', 'requestedBy', 'items'])->findOrFail($requestUuid);

        return view('gtk-requests.show', compact('gtkRequest', 'userId'));
    }

    public function submit(string $id)
    {
        $gtkRequest = GtkRequest::with('items')->findOrFail($id);
        $gtkRequest->status = 'submitted';
        $gtkRequest->save();

        return back()->with('success', 'Request GTK berhasil diajukan.');
    }

    public function edit(string $requestUuid)
    {
        $gtkRequest = GtkRequest::with('items')->findOrFail($requestUuid);

        if (request()->wantsJson()) {
            return response()->json(['request' => $gtkRequest->load('items')]);
        }

        $workUnits = WorkUnit::orderBy('name')->get();
        $academicYears = AcademicYear::active()->orderByDesc('start_date')->get();
        $gtkProfiles = GtkProfile::with('user')->get();

        return view('gtk-requests.edit', compact('gtkRequest', 'workUnits', 'academicYears', 'gtkProfiles'));
    }

    public function update(Request $request, string $requestUuid)
    {
        $type = $request->input('type');

        $baseRules = [
            'work_unit_id' => 'required|exists:work_units,id',
            'type' => 'required|in:procurement,trial,status_increase',
            'status' => 'in:draft,submitted',
        ];

        if ($type === GtkRequest::TYPE_PROCUREMENT) {
            $baseRules['academic_year_id'] = 'required|exists:academic_years,id';
            $baseRules['notes'] = 'nullable|string';
        }
        if ($type === GtkRequest::TYPE_TRIAL) {
            $baseRules['letter_number'] = 'nullable|string|max:100';
            $baseRules['letter_subject'] = 'nullable|string|max:255';
            $baseRules['letter_attachment'] = 'nullable|string|max:100';
            $baseRules['established_city'] = 'nullable|string|max:100';
            $baseRules['established_date'] = 'nullable|date';
        }

        $validated = $request->validate($baseRules);

        $gtkRequest = GtkRequest::findOrFail($requestUuid);
        $gtkRequest->update($validated);

        $this->saveItems($gtkRequest, $type, $request);

        if ($request->wantsJson()) {
            return response()->json(['request' => $gtkRequest->load('items')]);
        }

        return redirect()->route('gtk-requests.show', $gtkRequest->id)
            ->with('success', 'Request GTK berhasil diperbarui.');
    }

    public function destroy(string $requestUuid)
    {
        $gtkRequest = GtkRequest::findOrFail($requestUuid);
        GtkRequestItem::where('gtk_request_id', $gtkRequest->id)->delete();
        $gtkRequest->delete();

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Request GTK berhasil dihapus.']);
        }

        return redirect()->route('gtk-requests.index')
            ->with('success', 'Request GTK berhasil dihapus.');
    }

    // ── Item saving ─────────────────────────────────────────────────

    private function saveItems(GtkRequest $gtkRequest, string $type, Request $request): void
    {
        GtkRequestItem::where('gtk_request_id', $gtkRequest->id)->delete();

        if ($type === GtkRequest::TYPE_PROCUREMENT) {
            $items = $request->input('items', []);
            foreach ($items as $i => $item) {
                if (empty($item['jabatan'])) {
                    continue;
                }
                GtkRequestItem::create([
                    'gtk_request_id' => $gtkRequest->id,
                    'item_type' => $type,
                    'jabatan' => $item['jabatan'] ?? null,
                    'kebutuhan_ideal' => $item['kebutuhan_ideal'] ?? 0,
                    'gtk_yang_ada' => $item['gtk_yang_ada'] ?? 0,
                    'kualifikasi_minimal' => $item['kualifikasi_minimal'] ?? null,
                    'kebutuhan_tambahan' => $item['kebutuhan_tambahan'] ?? 0,
                    'keterangan' => $item['keterangan'] ?? null,
                    'order' => $i,
                ]);
            }
        }

        if ($type === GtkRequest::TYPE_TRIAL) {
            $items = $request->input('items', []);
            foreach ($items as $i => $item) {
                if (empty($item['nama'])) {
                    continue;
                }
                GtkRequestItem::create([
                    'gtk_request_id' => $gtkRequest->id,
                    'item_type' => $type,
                    'nupy' => $item['nupy'] ?? null,
                    'nama' => $item['nama'] ?? null,
                    'tugas' => $item['tugas'] ?? null,
                    'lembaga' => $item['lembaga'] ?? null,
                    'status_gtk' => $item['status_gtk'] ?? null,
                    'tmt' => $item['tmt'] ?? null,
                    'order' => $i,
                ]);
            }
        }

        if ($type === GtkRequest::TYPE_STATUS_INCREASE) {
            $items = $request->input('items', []);
            foreach ($items as $i => $item) {
                if (empty($item['nama'])) {
                    continue;
                }
                GtkRequestItem::create([
                    'gtk_request_id' => $gtkRequest->id,
                    'item_type' => $type,
                    'nama' => $item['nama'] ?? null,
                    'tugas' => $item['tugas'] ?? null,
                    'lembaga' => $item['lembaga'] ?? null,
                    'status_gtk' => $item['status_gtk'] ?? null,
                    'tmt' => $item['tmt'] ?? null,
                    'order' => $i,
                ]);
            }
        }
    }
}
