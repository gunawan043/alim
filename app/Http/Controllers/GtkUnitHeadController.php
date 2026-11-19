<?php

namespace App\Http\Controllers;

use App\Models\GtkWorkUnit;
use App\Models\User;
use App\Models\WorkUnit;
use App\Support\GtkUnitHeadAssignment;
use App\Support\UnitHeadAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Penetapan kepala & wakil satuan kerja.
 *
 * Data ini sengaja dipisahkan dari import Excel: upload GTK tidak boleh
 * mengubah siapa kepala/wakil, supaya tidak terjadi salah orang.
 */
class GtkUnitHeadController extends Controller
{
    public function index(Request $request, string $userId)
    {
        abort_unless(UnitHeadAccess::canManage($request->user()), 403);

        $workUnits = WorkUnit::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $metadataUserIds = $workUnits
            ->flatMap(fn (WorkUnit $unit) => [
                $unit->metadata['head_user_id'] ?? null,
                $unit->metadata['vice_head_user_id'] ?? null,
            ])
            ->filter()
            ->unique()
            ->values();

        $metadataUsers = User::query()
            ->whereIn('id', $metadataUserIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        // Cadangan: kepala/wakil yang sudah tercatat lewat jabatan pada
        // penempatan GTK, supaya data lama tetap terlihat di halaman ini.
        $placements = GtkWorkUnit::query()
            ->with(['user:id,name', 'position:id,name'])
            ->whereIn('work_unit_id', $workUnits->pluck('id'))
            ->where(function ($query) {
                $query->where('jabatan', 'like', 'Kepala%')
                    ->orWhere('jabatan', 'like', 'Wakil%')
                    ->orWhereHas('position', function ($position) {
                        $position->where('name', 'like', 'Kepala%')
                            ->orWhere('name', 'like', 'Wakil%');
                    });
            })
            ->get()
            ->groupBy('work_unit_id');

        $units = $workUnits->map(function (WorkUnit $unit) use ($metadataUsers, $placements) {
            $headId = $unit->metadata['head_user_id'] ?? null;
            $viceId = $unit->metadata['vice_head_user_id'] ?? null;

            $head = $headId ? $metadataUsers->get($headId) : null;
            $vice = $viceId ? $metadataUsers->get($viceId) : null;

            $unitPlacements = $placements->get($unit->id, collect());

            $fallbackHead = $head ? null : $unitPlacements->first(
                fn (GtkWorkUnit $placement) => $this->isHeadPlacement($placement)
            );
            $fallbackVice = $vice ? null : $unitPlacements->first(
                fn (GtkWorkUnit $placement) => $this->isVicePlacement($placement)
            );

            return (object) [
                'id' => $unit->id,
                'name' => $unit->name,
                'code' => $unit->code,
                'head' => $head,
                'vice' => $vice,
                'fallback_head' => $fallbackHead?->user,
                'fallback_vice' => $fallbackVice?->user,
            ];
        });

        $gtkOptions = User::query()
            ->where('is_active', true)
            ->whereHas('employment')
            ->with(['employment:id,user_id,nupy'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('gtk.unit-heads', compact('units', 'gtkOptions', 'userId'));
    }

    public function update(Request $request, string $userId, string $workUnitId)
    {
        abort_unless(UnitHeadAccess::canManage($request->user()), 403);

        $workUnit = WorkUnit::findOrFail($workUnitId);

        $validated = $request->validate([
            'head_user_id' => ['nullable', 'exists:users,id'],
            'vice_head_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $headId = $validated['head_user_id'] ?? null;
        $viceId = $validated['vice_head_user_id'] ?? null;

        if ($headId && $viceId && (string) $headId === (string) $viceId) {
            return back()->withErrors([
                'head_user_id' => 'Kepala dan wakil tidak boleh orang yang sama.',
            ])->withInput();
        }

        $metadata = $workUnit->metadata ?? [];
        $oldHeadId = $metadata['head_user_id'] ?? null;
        $oldViceId = $metadata['vice_head_user_id'] ?? null;

        UnitHeadAccess::ensureCanChange($oldHeadId, $headId, $request->user());
        UnitHeadAccess::ensureCanChange($oldViceId, $viceId, $request->user());

        $assignment = app(GtkUnitHeadAssignment::class);

        DB::transaction(function () use ($assignment, $workUnit, $metadata, $headId, $viceId, $oldHeadId, $oldViceId) {
            // Lepas dulu supaya penukaran kepala <-> wakil tidak saling menimpa.
            if ($oldHeadId && (string) $oldHeadId !== (string) $headId) {
                if ($oldUser = User::find($oldHeadId)) {
                    $assignment->release(
                        $oldUser,
                        $workUnit,
                        $metadata['head_previous'] ?? null,
                        GtkUnitHeadAssignment::HEAD,
                    );
                }
            }

            if ($oldViceId && (string) $oldViceId !== (string) $viceId) {
                if ($oldUser = User::find($oldViceId)) {
                    $assignment->release(
                        $oldUser,
                        $workUnit,
                        $metadata['vice_previous'] ?? null,
                        GtkUnitHeadAssignment::VICE,
                    );
                }
            }

            if ($headId && (string) $headId !== (string) $oldHeadId) {
                $metadata['head_previous'] = $assignment->assign(
                    User::findOrFail($headId),
                    $workUnit,
                    GtkUnitHeadAssignment::HEAD,
                );
            }

            if ($viceId && (string) $viceId !== (string) $oldViceId) {
                $metadata['vice_previous'] = $assignment->assign(
                    User::findOrFail($viceId),
                    $workUnit,
                    GtkUnitHeadAssignment::VICE,
                );
            }

            if (! $headId) {
                unset($metadata['head_previous']);
            }

            if (! $viceId) {
                unset($metadata['vice_previous']);
            }

            $metadata['head_user_id'] = $headId;
            $metadata['vice_head_user_id'] = $viceId;

            $workUnit->update(['metadata' => $metadata]);
        });

        return back()->with('success', "Kepala & wakil {$workUnit->name} berhasil diperbarui.");
    }

    private function isHeadPlacement(GtkWorkUnit $placement): bool
    {
        return $this->isHeadName($placement->jabatan)
            || $this->isHeadName($placement->position?->name);
    }

    private function isVicePlacement(GtkWorkUnit $placement): bool
    {
        return $this->isViceName($placement->jabatan)
            || $this->isViceName($placement->position?->name);
    }

    private function isHeadName(?string $name): bool
    {
        $name = strtolower(trim((string) $name));

        return $name === 'kepala' || str_starts_with($name, 'kepala ');
    }

    private function isViceName(?string $name): bool
    {
        $name = strtolower(trim((string) $name));

        return $name === 'wakil' || str_starts_with($name, 'wakil ');
    }
}
