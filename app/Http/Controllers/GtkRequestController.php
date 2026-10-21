<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\GtkProfile;
use App\Models\GtkRequest;
use App\Models\GtkRequestItem;
use App\Models\WorkUnit;
use App\Services\NotificationBroadcastService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GtkRequestController extends Controller
{
    public function index(Request $request)
    {
        $userId = request()->route('userId');
        $user = $request->user();
        $query = GtkRequest::with(['workUnit', 'requestedBy', 'items'])
            ->when(! $user->hasRole('Humas Personalia') && ! $user->isSystemAdmin() && ! $user->isSuperAdmin(), fn ($q) => $q->where('requested_by', $user->id))
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
        $user = $request->user();
        abort_unless($gtkRequest->requested_by === $user->id || $user->hasRole('Humas Personalia') || $user->isSystemAdmin() || $user->isSuperAdmin(), 403);

        return view('gtk-requests.show', compact('gtkRequest', 'userId'));
    }

    public function submit(Request $request, string $id)
    {
        $gtkRequest = GtkRequest::with(['items', 'workUnit', 'requestedBy'])->findOrFail($id);
        abort_unless($gtkRequest->requested_by === $request->user()->id || $request->user()->hasRole('Humas Personalia') || $request->user()->isSystemAdmin() || $request->user()->isSuperAdmin(), 403);

        if ($gtkRequest->status !== GtkRequest::STATUS_DRAFT) {
            return back()->with('error', 'Request sudah diajukan.');
        }

        $gtkRequest->update(['status' => GtkRequest::STATUS_SUBMITTED]);
        $this->notifyPersonalia($gtkRequest, $request->user());

        return back()->with('success', 'Request GTK berhasil diajukan dan notifikasi dikirim ke Personalia.');
    }

    public function approve(Request $request, string $id)
    {
        $gtkRequest = GtkRequest::findOrFail($id);
        $this->authorizePersonalia($request);
        abort_unless($gtkRequest->status === GtkRequest::STATUS_SUBMITTED, 422, 'Hanya request yang diajukan yang dapat disetujui.');
        $gtkRequest->update([
            'status' => GtkRequest::STATUS_APPROVED,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);
        $this->notifyRequester($gtkRequest, 'approved');

        return back()->with('success', 'Request GTK disetujui.');
    }

    public function reject(Request $request, string $id)
    {
        $validated = $request->validate(['rejection_reason' => 'required|string|max:1000']);
        $gtkRequest = GtkRequest::findOrFail($id);
        $this->authorizePersonalia($request);
        abort_unless($gtkRequest->status === GtkRequest::STATUS_SUBMITTED, 422, 'Hanya request yang diajukan yang dapat ditolak.');
        $gtkRequest->update([
            'status' => GtkRequest::STATUS_REJECTED,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);
        $this->notifyRequester($gtkRequest, 'rejected', $validated['rejection_reason']);

        return back()->with('success', 'Request GTK ditolak.');
    }

    private function authorizePersonalia(Request $request): void
    {
        $user = $request->user();
        abort_unless($user->hasRole('Humas Personalia') || $user->isSystemAdmin() || $user->isSuperAdmin(), 403, 'Hanya Personalia yang dapat memproses request ini.');
    }

    private function notifyPersonalia(GtkRequest $gtkRequest, $requester): void
    {
        $service = app(NotificationBroadcastService::class);
        $userIds = \App\Models\User::where('is_active', true)
            ->role('Humas Personalia')
            ->where('id', '!=', $requester->id)
            ->pluck('id');

        foreach ($userIds as $personaliaId) {
            $service->send((string) $personaliaId, [
                'module' => 'gtk',
                'type' => 'warning',
                'priority' => 'high',
                'title' => 'Pengajuan GTK baru',
                'message' => ($gtkRequest->workUnit?->name ?? 'Divisi').' mengajukan '.$gtkRequest->type_text.'.',
                'action_url' => route('user.gtk-requests.show', ['userId' => $personaliaId, 'requestUuid' => $gtkRequest->id]),
                'reference_type' => GtkRequest::class,
                'reference_id' => $gtkRequest->id,
                'action_text' => 'Tinjau Pengajuan',
            ]);
        }
    }

    private function notifyRequester(GtkRequest $gtkRequest, string $status, ?string $reason = null): void
    {
        $requesterId = $gtkRequest->requested_by;
        if (! $requesterId) {
            return;
        }
        app(NotificationBroadcastService::class)->send((string) $requesterId, [
            'module' => 'gtk',
            'type' => $status === 'approved' ? 'success' : 'error',
            'priority' => 'high',
            'title' => $status === 'approved' ? 'Pengajuan GTK disetujui' : 'Pengajuan GTK ditolak',
            'message' => 'Pengajuan '.$gtkRequest->type_text.' '.($status === 'approved' ? 'telah disetujui.' : 'ditolak.'.($reason ? ' Alasan: '.$reason : '')),
            'action_url' => route('user.gtk-requests.show', ['userId' => $requesterId, 'requestUuid' => $gtkRequest->id]),
            'reference_type' => GtkRequest::class,
            'reference_id' => $gtkRequest->id,
            'action_text' => 'Lihat Pengajuan',
        ]);
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
