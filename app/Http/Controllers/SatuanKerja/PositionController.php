<?php

namespace App\Http\Controllers\SatuanKerja;

use App\Models\GtkAdditionalTask;
use App\Models\GtkWorkUnit;
use App\Models\InstitutionDecree;
use App\Models\OtherTeacherTask;
use App\Models\StructuralPosition;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PositionController
{
    public function index(Request $request, string $userId, string $workUnitId)
    {
        $user = auth()->user();
        $workUnit = WorkUnit::where('id', $workUnitId)
            ->where('is_active', true)
            ->firstOrFail();

        $userWorkUnits = GtkWorkUnit::where('user_id', $user->id)
            ->whereHas('workUnit', fn($q) => $q->where('id', $workUnitId))
            ->exists();

        abort_unless($userWorkUnits, 403, 'Anda tidak memiliki akses ke satuan kerja ini.');

        $query = User::with(['employment', 'gtkProfile', 'gtkWorkUnits.workUnit'])
            ->whereHas('employments')
            ->whereHas('gtkWorkUnits', fn($q) => $q->where('work_unit_id', $workUnitId));

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if ($request->filled('jabatan_id')) {
            $query->whereHas('employment', fn($q) => $q->where('jabatan_id', $request->jabatan_id));
        }

        $jabatans = StructuralPosition::active()->orderBy('urutan')->orderBy('name')->get();
        $orders = $query->orderBy('name')->get();

        return view('satuan-kerja.positions.index', compact('orders', 'jabatans', 'workUnit', 'userId'));
    }

    public function update(Request $request, string $userId, string $workUnitId, string $id)
    {
        $user = auth()->user();
        $workUnit = WorkUnit::where('id', $workUnitId)->where('is_active', true)->firstOrFail();

        $validated = $request->validate([
            'jabatan_id' => 'nullable|exists:structural_positions,id',
        ]);

        DB::transaction(function () use ($request, $id, $validated, $workUnitId) {
            $userModel = User::where('id', $id)
                ->whereHas('gtkWorkUnits', fn($q) => $q->where('work_unit_id', $workUnitId))
                ->whereHas('employment')
                ->firstOrFail();

            $jabatan = StructuralPosition::find($validated['jabatan_id']);
            $userModel->employment?->update([
                'jabatan_id' => $validated['jabatan_id'],
                'jabatan' => $jabatan?->name,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Jabatan berhasil diperbarui.',
        ]);
    }

    public function massUpdate(Request $request, string $userId, string $workUnitId)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|uuid',
            'jabatan_id' => 'nullable|exists:structural_positions,id',
        ]);

        $updated = 0;

        DB::transaction(function () use ($validated, $workUnitId, &$updated) {
            foreach ($validated['ids'] as $id) {
                $userModel = User::where('id', $id)
                    ->whereHas('gtkWorkUnits', fn($q) => $q->where('work_unit_id', $workUnitId))
                    ->whereHas('employment')
                    ->first();

                if (!$userModel) {
                    continue;
                }

                $jabatan = StructuralPosition::find($validated['jabatan_id']);
                $userModel->employment?->update([
                    'jabatan_id' => $validated['jabatan_id'],
                    'jabatan' => $jabatan?->name,
                ]);

                $updated++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Berhasil memperbarui {$updated} GTK",
            'updated' => $updated,
        ]);
    }
}
