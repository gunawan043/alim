<?php

namespace App\Http\Controllers\Api\Pos\V1;

use App\Models\WorkUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Satuan Kerja (work unit) master data for the POS / Kasir integration.
 *
 * GET /api/pos/v1/satuan-kerja
 * GET /api/pos/v1/satuan-kerja/{id}
 */
class SatuanKerjaController extends PosApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = WorkUnit::query()
            ->with([
                'parent:id,name',
                'divisi:id,nama,kode',
                'school:id,work_unit_id,name,npsn',
            ])
            ->withCount('gtkWorkUnits');

        if (($search = $this->searchTerm($request)) !== null) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('divisi_id')) {
            $query->where('divisi_id', $request->string('divisi_id')->toString());
        }

        if ($request->input('is_active', '1') !== 'all') {
            $query->where('is_active', $request->boolean('is_active', true));
        }

        $paginator = $query
            ->orderBy('name')
            ->paginate($this->perPage($request))
            ->through(fn (WorkUnit $workUnit) => $this->format($workUnit));

        return $this->paginated($paginator, null, 'Daftar satuan kerja berhasil diambil.');
    }

    public function show(string $id): JsonResponse
    {
        $workUnit = WorkUnit::query()
            ->with([
                'parent:id,name',
                'divisi:id,nama,kode',
                'school:id,work_unit_id,name,npsn',
            ])
            ->withCount('gtkWorkUnits')
            ->find($id);

        if (! $workUnit) {
            return $this->notFound('Satuan kerja tidak ditemukan.');
        }

        return $this->ok($this->format($workUnit), 'Detail satuan kerja berhasil diambil.');
    }

    /**
     * @return array<string, mixed>
     */
    private function format(WorkUnit $workUnit): array
    {
        return [
            'id' => $workUnit->id,
            'kode' => $workUnit->code,
            'nama' => $workUnit->name,
            'jenis' => $workUnit->type,
            'induk' => $workUnit->parent ? [
                'id' => $workUnit->parent->id,
                'nama' => $workUnit->parent->name,
            ] : null,
            'divisi' => $workUnit->divisi ? [
                'id' => $workUnit->divisi->id,
                'nama' => $workUnit->divisi->nama,
                'kode' => $workUnit->divisi->kode,
            ] : null,
            'sekolah' => $workUnit->school ? [
                'id' => $workUnit->school->id,
                'nama' => $workUnit->school->name,
                'npsn' => $workUnit->school->npsn,
            ] : null,
            'jumlah_pegawai' => (int) ($workUnit->gtk_work_units_count ?? 0),
            'is_active' => (bool) $workUnit->is_active,
            'diperbarui_pada' => $workUnit->updated_at?->toIso8601String(),
        ];
    }
}
