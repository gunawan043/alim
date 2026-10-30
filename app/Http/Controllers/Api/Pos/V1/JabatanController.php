<?php

namespace App\Http\Controllers\Api\Pos\V1;

use App\Models\StructuralPosition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Jabatan (structural position) master data for the POS / Kasir integration.
 *
 * GET /api/pos/v1/jabatan
 */
class JabatanController extends PosApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = StructuralPosition::query()->with('jenisGtk:id,nama');

        if (($search = $this->searchTerm($request)) !== null) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('jenis_gtk_id')) {
            $query->where('jenis_gtk_id', $request->string('jenis_gtk_id')->toString());
        }

        if ($request->input('is_active', '1') !== 'all') {
            $query->where('is_active', $request->boolean('is_active', true));
        }

        $paginator = $query
            ->orderBy('hierarchy_level', 'desc')
            ->orderBy('urutan')
            ->orderBy('name')
            ->paginate($this->perPage($request, 100))
            ->through(fn (StructuralPosition $position) => [
                'id' => $position->id,
                'kode' => $position->code,
                'nama' => $position->name,
                'kategori' => $position->kategori,
                'level' => $position->level,
                'hierarchy_level' => (int) $position->hierarchy_level,
                'urutan' => (int) $position->urutan,
                'deskripsi' => $position->description,
                'jenis_gtk' => $position->jenisGtk ? [
                    'id' => $position->jenisGtk->id,
                    'nama' => $position->jenisGtk->nama,
                ] : null,
                'is_active' => (bool) $position->is_active,
                'diperbarui_pada' => $position->updated_at?->toIso8601String(),
            ]);

        return $this->paginated($paginator, null, 'Daftar jabatan berhasil diambil.');
    }
}
