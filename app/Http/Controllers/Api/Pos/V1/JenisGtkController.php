<?php

namespace App\Http\Controllers\Api\Pos\V1;

use App\Models\JenisGtk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Jenis GTK master data for the POS / Kasir integration.
 *
 * GET /api/pos/v1/jenis-gtk
 */
class JenisGtkController extends PosApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = JenisGtk::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where('nama', 'like', "%{$search}%");
        }

        if ($request->input('is_active', '1') !== 'all') {
            $query->where('is_active', $request->boolean('is_active', true));
        }

        $paginator = $query
            ->orderBy('urutan')
            ->orderBy('nama')
            ->paginate($this->perPage($request, 100))
            ->through(fn (JenisGtk $jenisGtk) => [
                'id' => $jenisGtk->id,
                'nama' => $jenisGtk->nama,
                'deskripsi' => $jenisGtk->deskripsi,
                'urutan' => (int) $jenisGtk->urutan,
                'is_active' => (bool) $jenisGtk->is_active,
                'diperbarui_pada' => $jenisGtk->updated_at?->toIso8601String(),
            ]);

        return $this->paginated($paginator, null, 'Daftar jenis GTK berhasil diambil.');
    }
}
