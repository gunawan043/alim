<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetRoom;
use App\Models\School;
use App\Models\StockOpnameItem;
use App\Models\StockOpnameOfficer;
use App\Models\StockOpnameSession;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SarprasStockOpnameController extends SarprasBaseController
{
    public function index(Request $request)
    {
        $query = StockOpnameSession::with(['workUnit', 'creator', 'room', 'category'])
            ->withCount([
                'items',
                'items as observed_count' => fn ($q) => $q->whereNotIn('observed_status', [null, 'pending']),
            ]);

        $query = $this->scopeToAccessibleWorkUnits($query, $request);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$s}%")
                ->orWhere('session_code', 'like', "%{$s}%"));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('work_unit_id')) {
            $query->where('work_unit_id', $request->work_unit_id);
        }

        $stats = [
            'total' => (clone $query)->count(),
            'planned' => (clone $query)->where('status', StockOpnameSession::STATUS_PLANNED)->count(),
            'running' => (clone $query)->where('status', StockOpnameSession::STATUS_IN_PROGRESS)->count(),
            'closed' => (clone $query)->where('status', StockOpnameSession::STATUS_CLOSED)->count(),
        ];

        $sessions = $query->orderByDesc('scheduled_date')->paginate(15)->withQueryString();
        $workUnits = WorkUnit::query()
            ->when(! $this->isCentralSarpras(), fn ($q) => $q->whereIn('id', $this->accessibleWorkUnitIds()))
            ->orderBy('name')->get();

        return view('sarpras.stock-opname.index', compact('sessions', 'stats', 'workUnits'));
    }

    public function create(Request $request)
    {
        $workUnits = WorkUnit::query()
            ->when(! $this->isCentralSarpras(), fn ($q) => $q->whereIn('id', $this->accessibleWorkUnitIds()))
            ->orderBy('name')->get();

        $rooms = AssetRoom::with('building')->where('is_active', true)
            ->when(! $this->isCentralSarpras(), fn ($q) => $q->whereIn('work_unit_id', $this->accessibleWorkUnitIds()))
            ->orderBy('room_name')->get();

        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();

        $users = User::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereHas('gtkWorkUnits')
                    ->orWhereHas('roles');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('sarpras.stock-opname.create', compact('workUnits', 'rooms', 'categories', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'work_unit_id' => 'required|uuid|exists:work_units,id',
            'room_id' => 'nullable|uuid|exists:asset_rooms,id',
            'category_id' => 'nullable|uuid|exists:asset_categories,id',
            'title' => 'required|string|max:191',
            'description' => 'nullable|string|max:2000',
            'scheduled_date' => 'required|date',
            'officers' => 'required|array|min:1',
            'officers.*' => 'uuid|exists:users,id',
        ]);

        $this->authorizeWorkUnitAccess($validated['work_unit_id'], $request);

        if (! $this->isCentralSarpras() && ! $this->isUnitSarprasOfficer()) {
            if (empty($validated['room_id'])) {
                return back()->withInput()->with('error', 'Wali kelas/wali kamar wajib memilih ruang tanggung jawabnya.');
            }
            $this->authorizeResponsibleRoom($validated['room_id'], $request);
        }

        $assetQuery = Asset::query()
            ->where('work_unit_id', $validated['work_unit_id'])
            ->where('is_active', true)
            ->whereNotIn('status', ['disposed', 'dihapus']);

        if (! empty($validated['room_id'])) {
            $assetQuery->where('room_id', $validated['room_id']);
        }
        if (! empty($validated['category_id'])) {
            $assetQuery->where('asset_category_id', $validated['category_id']);
        }

        $assets = $assetQuery->get();
        if ($assets->isEmpty()) {
            return back()->withInput()->with('error', 'Tidak ada aset pada cakupan tersebut. Periksa satuan kerja/ruang/kategori.');
        }

        $schoolId = null;
        if (! empty($validated['room_id'])) {
            $schoolId = AssetRoom::query()->whereKey($validated['room_id'])->value('school_id');
        } else {
            $schoolId = School::query()->where('work_unit_id', $validated['work_unit_id'])->value('id');
        }

        $session = DB::transaction(function () use ($validated, $assets, $schoolId) {
            $session = StockOpnameSession::create([
                'session_code' => $this->generateSessionCode(),
                'work_unit_id' => $validated['work_unit_id'],
                'school_id' => $schoolId,
                'room_id' => $validated['room_id'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'scheduled_date' => $validated['scheduled_date'],
                'status' => StockOpnameSession::STATUS_PLANNED,
                'created_by' => auth()->id(),
            ]);

            foreach (array_unique($validated['officers']) as $userId) {
                StockOpnameOfficer::create([
                    'session_id' => $session->id,
                    'user_id' => $userId,
                    'role' => $userId === auth()->id() ? 'lead' : 'officer',
                ]);
            }

            foreach ($assets as $asset) {
                StockOpnameItem::create([
                    'session_id' => $session->id,
                    'asset_id' => $asset->id,
                    'expected_status' => $asset->status,
                    'expected_condition' => $asset->condition,
                    'expected_room_id' => $asset->room_id,
                    'observed_status' => 'pending',
                ]);
            }

            return $session;
        });

        return redirect()->route('sarpras.stock-opname.show', $session->id)
            ->with('success', 'Sesi stock opname dibuat dengan '.$assets->count().' aset.');
    }

    public function show(Request $request, string $id)
    {
        $session = StockOpnameSession::with(['workUnit', 'creator', 'room.building', 'category', 'officers.user'])
            ->findOrFail($id);
        $this->authorizeWorkUnitAccess($session->work_unit_id, $request);

        $itemsQuery = StockOpnameItem::with(['asset.category', 'asset.room', 'asset.workUnit', 'recorder'])
            ->where('session_id', $session->id);

        if ($request->filled('observation')) {
            $itemsQuery->where('observed_status', $request->observation);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $itemsQuery->whereHas('asset', fn ($q) => $q
                ->where('asset_name', 'like', "%{$s}%")
                ->orWhere('asset_code', 'like', "%{$s}%"));
        }

        $items = $itemsQuery->orderBy('id')->paginate(25)->withQueryString();

        $allItems = StockOpnameItem::where('session_id', $session->id);
        $stats = [
            'total' => (clone $allItems)->count(),
            'found' => (clone $allItems)->where('observed_status', 'found')->count(),
            'missing' => (clone $allItems)->where('observed_status', 'missing')->count(),
            'damaged' => (clone $allItems)->where('observed_status', 'damaged')->count(),
            'moved' => (clone $allItems)->where('observed_status', 'moved')->count(),
            'pending' => (clone $allItems)->where('observed_status', 'pending')->count(),
        ];
        $stats['percent'] = $stats['total'] > 0
            ? round(($stats['total'] - $stats['pending']) / $stats['total'] * 100, 1)
            : 0;

        $rooms = AssetRoom::with('building')->where('is_active', true)
            ->when($session->work_unit_id, fn ($q) => $q->where('work_unit_id', $session->work_unit_id))
            ->orderBy('room_name')->get();

        $canManage = $this->isCentralSarpras() || in_array($session->work_unit_id, $this->accessibleWorkUnitIds(), true);

        return view('sarpras.stock-opname.show', compact('session', 'items', 'stats', 'rooms', 'canManage'));
    }

    public function start(Request $request, string $id)
    {
        $session = StockOpnameSession::findOrFail($id);
        $this->authorizeWorkUnitAccess($session->work_unit_id, $request);
        abort_unless($session->status === StockOpnameSession::STATUS_PLANNED, 422, 'Sesi tidak dapat dimulai.');

        $session->update([
            'status' => StockOpnameSession::STATUS_IN_PROGRESS,
            'started_date' => today(),
        ]);

        return back()->with('success', 'Stock opname dimulai. Petugas dapat melakukan scan.');
    }

    public function record(Request $request, string $id, string $itemId)
    {
        $session = StockOpnameSession::findOrFail($id);
        $this->authorizeWorkUnitAccess($session->work_unit_id, $request);
        abort_unless($session->status === StockOpnameSession::STATUS_IN_PROGRESS, 422, 'Mulai sesi sebelum mencatat temuan.');

        $validated = $request->validate([
            'observed_status' => 'required|in:found,missing,damaged,moved',
            'observed_condition' => 'nullable|in:baik,rusak_ringan,rusak_sedang,rusak_berat',
            'observed_room_id' => 'nullable|uuid|exists:asset_rooms,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $item = StockOpnameItem::with('asset')->where('session_id', $session->id)->findOrFail($itemId);

        DB::transaction(function () use ($item, $validated) {
            $condition = $validated['observed_condition'] ?? $item->expected_condition ?? 'baik';
            $observedRoom = $validated['observed_room_id'] ?? $item->asset->room_id;

            $item->update([
                'observed_status' => $validated['observed_status'],
                'observed_condition' => $condition,
                'observed_room_id' => $observedRoom,
                'notes' => $validated['notes'] ?? null,
                'recorded_by' => auth()->id(),
                'scanned_at' => now(),
            ]);

            $assetUpdate = ['condition' => $condition];

            if ($validated['observed_status'] === 'missing') {
                $assetUpdate = ['condition' => 'hilang', 'status' => 'hilang'];
            } elseif ($validated['observed_status'] === 'moved' && $observedRoom) {
                $assetUpdate['room_id'] = $observedRoom;
            } elseif ($validated['observed_status'] === 'damaged') {
                $assetUpdate['condition'] = $validated['observed_condition'] ?? 'rusak_sedang';
            }

            $item->asset->update($assetUpdate);
        });

        return back()->with('success', 'Temuan aset berhasil dicatat.');
    }

    public function scan(Request $request, string $id)
    {
        $session = StockOpnameSession::findOrFail($id);
        $this->authorizeWorkUnitAccess($session->work_unit_id, $request);
        abort_unless($session->status === StockOpnameSession::STATUS_IN_PROGRESS, 422, 'Mulai sesi sebelum scan.');

        $validated = $request->validate(['code' => 'required|string|max:100']);
        $code = trim($validated['code']);

        $asset = Asset::query()
            ->where('asset_code', $code)
            ->orWhere('id', $code)
            ->first();

        if (! $asset) {
            return back()->with('error', 'Kode aset tidak ditemukan: '.$code);
        }

        $item = StockOpnameItem::where('session_id', $session->id)->where('asset_id', $asset->id)->first();
        if (! $item) {
            return back()->with('error', 'Aset '.$asset->asset_name.' tidak termasuk dalam sesi ini.');
        }

        $item->update([
            'observed_status' => 'found',
            'observed_condition' => $asset->condition,
            'observed_room_id' => $asset->room_id,
            'recorded_by' => auth()->id(),
            'scanned_at' => now(),
        ]);

        return back()->with('success', 'Aset ditemukan: '.$asset->asset_name.' ('.$asset->asset_code.').');
    }

    public function close(Request $request, string $id)
    {
        $session = StockOpnameSession::findOrFail($id);
        $this->authorizeWorkUnitAccess($session->work_unit_id, $request);
        abort_unless($session->status === StockOpnameSession::STATUS_IN_PROGRESS, 422, 'Sesi tidak dapat ditutup.');

        $validated = $request->validate([
            'summary_notes' => 'nullable|string|max:2000',
        ]);

        $stats = $session->items()
            ->selectRaw("observed_status, COUNT(*) as total")
            ->groupBy('observed_status')
            ->pluck('total', 'observed_status');

        $summary = collect([
            'found' => $stats['found'] ?? 0,
            'missing' => $stats['missing'] ?? 0,
            'damaged' => $stats['damaged'] ?? 0,
            'moved' => $stats['moved'] ?? 0,
            'pending' => $stats['pending'] ?? 0,
        ])->map(fn ($count, $status) => $status.': '.$count)->implode(', ');

        $session->update([
            'status' => StockOpnameSession::STATUS_CLOSED,
            'closed_date' => today(),
            'closed_by' => auth()->id(),
            'summary_notes' => trim(($validated['summary_notes'] ?? '').' '.$summary),
        ]);

        return redirect()->route('sarpras.stock-opname.show', $session->id)
            ->with('success', 'Stock opname ditutup. Rekap: '.$summary);
    }

    public function cancel(Request $request, string $id)
    {
        $session = StockOpnameSession::findOrFail($id);
        $this->authorizeWorkUnitAccess($session->work_unit_id, $request);
        abort_unless($session->status === StockOpnameSession::STATUS_PLANNED, 422, 'Hanya sesi rencana yang dapat dibatalkan.');

        $session->update(['status' => StockOpnameSession::STATUS_CANCELLED]);

        return back()->with('success', 'Sesi stock opname dibatalkan.');
    }

    private function generateSessionCode(): string
    {
        do {
            $code = 'SO-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));
        } while (StockOpnameSession::where('session_code', $code)->exists());

        return $code;
    }
}
