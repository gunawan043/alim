<?php

namespace App\Http\Controllers;

use App\Models\StructuralPosition;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GtkPositionController extends Controller
{
    public function index(Request $request, string $userId)
    {
        abort_unless(canPermission('gtk-update'), 403, 'Anda tidak memiliki izin untuk mengelola jabatan GTK.');

        $query = User::with(['employment', 'gtkProfile'])
            ->whereHas('employment');

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $query->whereHas('employment', fn ($q) => $q->where('school_id', $schoolId));
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if ($request->filled('jabatan_id')) {
            $query->whereHas('employment', fn ($q) => $q->where('jabatan_id', $request->jabatan_id));
        }

        if ($request->filled('status_kepegawaian')) {
            $query->whereHas('employment', fn ($q) => $q->where('status_kepegawaian', $request->status_kepegawaian));
        }

        if ($request->filled('jenis_gtk')) {
            $query->whereHas('employment', fn ($q) => $q->where('jenis_gtk', $request->jenis_gtk));
        }

        $orderBy = $request->get('order_by', 'name');
        $orderDir = $request->get('order_dir', 'asc');
        $query->orderBy($orderBy, $orderDir);

        $perPage = $request->get('per_page', 20);
        $gtks = $query->paginate($perPage)->withQueryString();

        $jabatans = StructuralPosition::active()->orderBy('urutan')->orderBy('name')->get();

        return view('gtk-positions.index', compact('gtks', 'jabatans', 'userId'));
    }

    public function update(Request $request, string $userId, string $id): JsonResponse
    {
        abort_unless(canPermission('gtk-update'), 403, 'Anda tidak memiliki izin untuk mengelola jabatan GTK.');

        $validated = $request->validate([
            'jabatan_id' => 'nullable|exists:structural_positions,id',
        ]);

        DB::transaction(function () use ($request, $id, $validated) {
            $user = User::where('id', $id)->whereHas('employment')->firstOrFail();

            $schoolId = $request->attributes->get('schoolContextId');
            if ($schoolId) {
                abort_unless(
                    $user->employment?->school_id === $schoolId,
                    403,
                    'Akses ditolak.'
                );
            }

            $jabatan = StructuralPosition::find($validated['jabatan_id']);

            $user->employment?->update([
                'jabatan_id' => $validated['jabatan_id'],
                'jabatan' => $jabatan?->name,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Jabatan berhasil diperbarui.',
        ]);
    }

    public function massUpdate(Request $request, string $userId): JsonResponse
    {
        abort_unless(canPermission('gtk-update'), 403, 'Anda tidak memiliki izin untuk mengelola jabatan GTK.');

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:users,id',
            'jabatan_id' => 'nullable|exists:structural_positions,id',
        ]);

        $schoolId = $request->attributes->get('schoolContextId');
        $updated = 0;

        DB::transaction(function () use ($validated, $schoolId, &$updated) {
            foreach ($validated['ids'] as $id) {
                $user = User::where('id', $id)
                    ->whereHas('employment')
                    ->first();

                if (! $user) {
                    continue;
                }

                if ($schoolId && $user->employment?->school_id !== $schoolId) {
                    continue;
                }

                $jabatan = StructuralPosition::find($validated['jabatan_id']);
                $user->employment?->update([
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
