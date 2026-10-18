<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\GtkWorkUnit;
use App\Models\User;
use App\Models\WorkUnit;
use App\Models\WorkUnitSarprasOfficer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SarprasOfficerController extends SarprasBaseController
{
    public function index(Request $request)
    {
        $isCentral = $this->isUrtLeadership();
        $headedIds = auth()->user()?->headedWorkUnitIds() ?? [];

        abort_if(! $isCentral && $headedIds === [], 403, 'Hanya kepala satuan kerja atau Unit Rumah Tangga yang dapat mengelola petugas Sarpras.');

        $canManage = true;

        $query = WorkUnitSarprasOfficer::with(['workUnit', 'user', 'assigner'])
            ->when(! $isCentral, fn ($q) => $q->whereIn('work_unit_id', $headedIds));

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%"))
                    ->orWhereHas('workUnit', fn ($u) => $u->where('name', 'like', "%{$s}%"));
            });
        }
        if ($request->filled('work_unit_id')) {
            $query->where('work_unit_id', $request->work_unit_id);
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $officers = $query->orderByDesc('is_active')->orderByDesc('is_primary')->paginate(15)->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('is_active', true)->count(),
            'primary' => (clone $query)->where('is_active', true)->where('is_primary', true)->count(),
            'units' => (clone $query)->where('is_active', true)->distinct('work_unit_id')->count('work_unit_id'),
        ];

        $workUnits = WorkUnit::query()
            ->when(! $isCentral, fn ($q) => $q->whereIn('id', $headedIds))
            ->orderBy('name')->get();

        $unitHasGtk = ! $isCentral
            && GtkWorkUnit::query()->whereIn('work_unit_id', $headedIds)->exists();

        $users = User::query()
            ->where('is_active', true)
            ->when($unitHasGtk, fn ($q) => $q->whereHas('gtkWorkUnits', fn ($g) => $g->whereIn('work_unit_id', $headedIds)))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('sarpras.petugas.index', compact('officers', 'stats', 'workUnits', 'users', 'canManage', 'isCentral'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'work_unit_id' => 'required|uuid|exists:work_units,id',
            'user_id' => 'required|uuid|exists:users,id',
            'is_primary' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->authorizeOfficerManagement($validated['work_unit_id']);

        if (! $this->isUrtLeadership()) {
            $unitHasGtk = GtkWorkUnit::query()
                ->where('work_unit_id', $validated['work_unit_id'])
                ->exists();

            if ($unitHasGtk) {
                $isUnitMember = GtkWorkUnit::query()
                    ->where('work_unit_id', $validated['work_unit_id'])
                    ->where('user_id', $validated['user_id'])
                    ->exists();

                abort_unless($isUnitMember, 422, 'GTK yang dipilih bukan anggota satuan kerja ini.');
            }
        }

        DB::transaction(function () use ($validated) {
            $isPrimary = (bool) ($validated['is_primary'] ?? false);

            if ($isPrimary) {
                WorkUnitSarprasOfficer::where('work_unit_id', $validated['work_unit_id'])
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            WorkUnitSarprasOfficer::updateOrCreate(
                [
                    'work_unit_id' => $validated['work_unit_id'],
                    'user_id' => $validated['user_id'],
                ],
                [
                    'is_primary' => $isPrimary,
                    'is_active' => true,
                    'assigned_by' => auth()->id(),
                    'assigned_at' => now(),
                    'notes' => $validated['notes'] ?? null,
                ]
            );
        });

        return back()->with('success', 'Petugas Sarpras satuan kerja berhasil disimpan.');
    }

    public function toggle(string $userId, Request $request, string $id)
    {
        $officer = WorkUnitSarprasOfficer::findOrFail($id);
        $this->authorizeOfficerManagement($officer->work_unit_id);
        $officer->update([
            'is_active' => ! $officer->is_active,
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
        ]);

        if (! $officer->is_active) {
            $officer->update(['is_primary' => false]);
        }

        return back()->with('success', $officer->is_active
            ? 'Petugas Sarpras diaktifkan kembali.'
            : 'Petugas Sarpras dinonaktifkan.');
    }

    public function setPrimary(string $userId, Request $request, string $id)
    {
        $officer = WorkUnitSarprasOfficer::findOrFail($id);
        $this->authorizeOfficerManagement($officer->work_unit_id);
        abort_unless($officer->is_active, 422, 'Aktifkan petugas terlebih dahulu.');

        DB::transaction(function () use ($officer) {
            WorkUnitSarprasOfficer::where('work_unit_id', $officer->work_unit_id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);

            $officer->update([
                'is_primary' => true,
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
            ]);
        });

        return back()->with('success', 'Petugas Sarpras utama satuan kerja diperbarui.');
    }

    private function authorizeOfficerManagement(string $workUnitId): void
    {
        if ($this->isUrtLeadership()) {
            return;
        }

        $user = auth()->user();
        abort_unless(
            $user && in_array($workUnitId, $user->headedWorkUnitIds(), true),
            403,
            'Anda hanya dapat mengatur petugas Sarpras untuk satuan kerja yang Anda pimpin.'
        );
    }
}
