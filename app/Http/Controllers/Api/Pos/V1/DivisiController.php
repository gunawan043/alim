<?php

namespace App\Http\Controllers\Api\Pos\V1;

use App\Models\Divisi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Divisi master data for the POS / Kasir integration.
 *
 * GET /api/pos/v1/divisi
 */
class DivisiController extends PosApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Divisi::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%");
            });
        }

        if ($request->input('is_active', '1') !== 'all') {
            $query->where('is_active', $request->boolean('is_active', true));
        }

        $paginator = $query
            ->orderBy('sort_order')
            ->orderBy('nama')
            ->paginate($this->perPage($request, 100))
            ->through(fn (Divisi $divisi) => [
                'id' => $divisi->id,
                'nama' => $divisi->nama,
                'kode' => $divisi->kode,
                'deskripsi' => $divisi->deskripsi,
                'sort_order' => (int) $divisi->sort_order,
                'is_active' => (bool) $divisi->is_active,
                'diperbarui_pada' => $divisi->updated_at?->toIso8601String(),
            ]);

        return $this->paginated($paginator, null, 'Daftar divisi berhasil diambil.');
    }
}
