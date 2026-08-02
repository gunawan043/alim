<?php

namespace App\Http\Controllers;

use App\Models\Divisi;
use App\Models\WorkUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WorkUnitController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkUnit::with('divisi')->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $workUnits = $query->get();

        $divisiOptions = Divisi::active()->orderBy('nama')->pluck('nama', 'id')->toArray();
        $parentOptions = WorkUnit::getParentOptions();

        $totalWorkUnits = WorkUnit::count();
        $activeWorkUnits = WorkUnit::active()->count();
        $inactiveWorkUnits = WorkUnit::inactive()->count();
        $totalDivisi = Divisi::count();

        $workUnitsByParent = [];
        foreach ($parentOptions as $key => $value) {
            if ($key === '') {
                continue;
            }
            $count = WorkUnit::where('induk', $key)->count();
            $workUnitsByParent[$value] = $count;
        }
        $countNoParent = WorkUnit::where(function ($q) {
            $q->whereNull('induk')->orWhere('induk', '');
        })->count();
        $workUnitsByParent['Tidak Ada Induk'] = $countNoParent;

        return view('work-units.index', compact(
            'workUnits',
            'divisiOptions',
            'parentOptions',
            'totalWorkUnits',
            'activeWorkUnits',
            'inactiveWorkUnits',
            'totalDivisi',
            'workUnitsByParent'
        ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'divisi_id' => 'nullable|exists:divisis,id',
            'type' => 'nullable',
            'induk' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $parentId = $request->induk === '' ? null : $request->induk;
            $code = WorkUnit::generateUniqueCode($request->divisi_id, $parentId, $request->type);

            $workUnit = WorkUnit::create([
                'name' => $request->name,
                'code' => $code,
                'divisi_id' => $request->divisi_id ?: null,
                'type' => $request->type ?: null,
                'induk' => $request->induk ?: null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Satuan kerja berhasil ditambahkan',
                'data' => $workUnit->load('divisi'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat satuan kerja: '.$e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $workUnit = WorkUnit::with('divisi')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $workUnit,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Satuan kerja tidak ditemukan',
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $workUnit = WorkUnit::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Satuan kerja tidak ditemukan',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'divisi_id' => 'nullable|exists:divisis,id',
            'type' => 'nullable',
            'induk' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $workUnit->update([
                'name' => $request->name,
                'divisi_id' => $request->divisi_id ?: null,
                'type' => $request->type ?: null,
                'induk' => $request->induk ?: null,
                'is_active' => $request->boolean('is_active', $workUnit->is_active),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Satuan kerja berhasil diperbarui',
                'data' => $workUnit->load('divisi'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui satuan kerja',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $workUnit = WorkUnit::findOrFail($id);

            $hasGtk = DB::table('gtk_work_unit')
                ->where('work_unit_id', $id)
                ->exists();

            if ($hasGtk) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus satuan kerja yang memiliki GTK',
                ], 400);
            }

            $workUnit->delete();

            return response()->json([
                'success' => true,
                'message' => 'Satuan kerja berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus satuan kerja: '.$e->getMessage(),
            ], 500);
        }
    }

    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'required|exists:work_units,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $workUnits = WorkUnit::whereIn('id', $request->ids)->get();

            foreach ($workUnits as $wu) {
                $hasGtk = DB::table('gtk_work_unit')
                    ->where('work_unit_id', $wu->id)
                    ->exists();

                if ($hasGtk) {
                    return response()->json([
                        'success' => false,
                        'message' => "Tidak dapat menghapus '{$wu->name}' — masih memiliki GTK",
                    ], 400);
                }
            }

            $deletedCount = WorkUnit::whereIn('id', $request->ids)->delete();

            return response()->json([
                'success' => true,
                'message' => "$deletedCount satuan kerja berhasil dihapus",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus satuan kerja: '.$e->getMessage(),
            ], 500);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $workUnit = WorkUnit::findOrFail($id);
            $workUnit->update(['is_active' => ! $workUnit->is_active]);

            $status = $workUnit->is_active ? 'diaktifkan' : 'dinonaktifkan';

            return response()->json([
                'success' => true,
                'message' => "Satuan kerja berhasil $status",
                'data' => $workUnit->load('divisi'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status',
            ], 500);
        }
    }

    public function generateCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'divisi_id' => 'nullable|exists:divisis,id',
            'type' => 'nullable',
            'induk' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $parentInduk = $request->induk === '' ? null : $request->induk;
        $code = WorkUnit::generateUniqueCode($request->divisi_id, $parentInduk, $request->type);

        return response()->json([
            'success' => true,
            'code' => $code,
        ]);
    }
}
