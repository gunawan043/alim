<?php

namespace App\Http\Controllers;

use App\Models\Jabatan;
use App\Models\JenisGtk;
use App\Models\WorkUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MasterDataController extends Controller
{
    // ============================================================
    // JENIS GTK
    // ============================================================
    public function jenisGtkIndex(Request $request)
    {
        $query = JenisGtk::query()->orderBy('urutan')->orderBy('nama');

        if ($request->filled('search')) {
            $query->where('nama', 'like', "%{$request->search}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $jenisGtkList = $query->withCount('jabatans')->paginate(20);

        return view('master-data.jenis-gtk-index', compact('jenisGtkList'));
    }

    public function jenisGtkStore(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:100|unique:jenis_gtk,nama',
            'deskripsi' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'urutan' => 'nullable|integer|min:0',
        ]);

        $data['id'] = Str::uuid();
        JenisGtk::create($data);

        return redirect()->route('user.master-data.jenis-gtk.index')
            ->with('success', 'Jenis GTK berhasil ditambahkan.');
    }

    public function jenisGtkUpdate(Request $request, string $id)
    {
        $jenis = JenisGtk::findOrFail($id);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('jenis_gtk', 'nama')->ignore($id)],
            'deskripsi' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'urutan' => 'nullable|integer|min:0',
        ]);

        $jenis->update($data);

        return redirect()->route('user.master-data.jenis-gtk.index')
            ->with('success', 'Jenis GTK berhasil diperbarui.');
    }

    public function jenisGtkDestroy(string $id)
    {
        JenisGtk::findOrFail($id)->delete();

        return redirect()->route('user.master-data.jenis-gtk.index')
            ->with('success', 'Jenis GTK berhasil dihapus.');
    }

    // ============================================================
    // JABATAN
    // ============================================================
    public function jabatanIndex(Request $request)
    {
        $query = Jabatan::with('jenisGtk')
            ->orderBy('jenis_gtk_id')
            ->orderBy('urutan')
            ->orderBy('nama');

        if ($request->filled('search')) {
            $query->where('nama', 'like', "%{$request->search}%");
        }

        if ($request->filled('jenis_gtk_id')) {
            $query->where('jenis_gtk_id', $request->jenis_gtk_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $jabatanList = $query->paginate(20);
        $jenisGtkList = JenisGtk::active()->orderBy('nama')->get();

        return view('master-data.jabatan-index', compact('jabatanList', 'jenisGtkList'));
    }

    public function jabatanStore(Request $request)
    {
        $data = $request->validate([
            'jenis_gtk_id' => 'required|exists:jenis_gtk,id',
            'nama' => [
                'required', 'string', 'max:150',
                Rule::unique('jabatan')->where(fn ($q) => $q->where('jenis_gtk_id', $request->jenis_gtk_id)
                ),
            ],
            'kategori' => 'nullable|string|max:50',
            'deskripsi' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'urutan' => 'nullable|integer|min:0',
        ]);

        $data['id'] = Str::uuid();
        Jabatan::create($data);

        return redirect()->route('user.master-data.jabatan.index')
            ->with('success', 'Jabatan berhasil ditambahkan.');
    }

    public function jabatanUpdate(Request $request, string $id)
    {
        $jabatan = Jabatan::findOrFail($id);

        $data = $request->validate([
            'jenis_gtk_id' => 'required|exists:jenis_gtk,id',
            'nama' => [
                'required', 'string', 'max:150',
                Rule::unique('jabatan')->where(fn ($q) => $q->where('jenis_gtk_id', $request->jenis_gtk_id)
                )->ignore($id),
            ],
            'kategori' => 'nullable|string|max:50',
            'deskripsi' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'urutan' => 'nullable|integer|min:0',
        ]);

        $jabatan->update($data);

        return redirect()->route('user.master-data.jabatan.index')
            ->with('success', 'Jabatan berhasil diperbarui.');
    }

    public function jabatanDestroy(string $id)
    {
        Jabatan::findOrFail($id)->delete();

        return redirect()->route('user.master-data.jabatan.index')
            ->with('success', 'Jabatan berhasil dihapus.');
    }

    // ============================================================
    // API: Get jabatan by jenis_gtk (for dropdown)
    // ============================================================
    public function getJabatanByJenis(Request $request)
    {
        $jabatan = Jabatan::active()
            ->where('jenis_gtk_id', $request->jenis_gtk_id)
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get(['id', 'nama', 'kategori']);

        return response()->json(['success' => true, 'data' => $jabatan]);
    }

    // ============================================================
    // SATUAN KERJA
    // ============================================================
    public function satuanKerjaIndex(Request $request, string $userId)
    {
        $query = WorkUnit::with('divisi')->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->filled('divisi_id')) {
            $query->where('divisi_id', $request->divisi_id);
        }

        $workUnits = $query->withCount(['gtkWorkUnits'])->paginate(20);

        $divisiOptions = \App\Models\Divisi::active()->orderBy('nama')->pluck('nama', 'id')->toArray();
        $parentOptions = WorkUnit::getParentOptions();

        $totalWorkUnits = WorkUnit::count();
        $activeWorkUnits = WorkUnit::active()->count();
        $inactiveWorkUnits = WorkUnit::inactive()->count();
        $totalDivisi = \App\Models\Divisi::count();

        return view('master-data.satuan-kerja-index', compact(
            'workUnits', 'divisiOptions', 'parentOptions',
            'totalWorkUnits', 'activeWorkUnits', 'inactiveWorkUnits', 'totalDivisi'
        ));
    }

    public function satuanKerjaStore(Request $request, string $userId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'divisi_id' => 'nullable|exists:divisis,id',
            'induk' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $parentId = $request->induk === '' ? null : $request->induk;
        $code = WorkUnit::generateUniqueCode($request->divisi_id, $parentId);

        WorkUnit::create([
            'name' => $request->name,
            'code' => $code,
            'divisi_id' => $request->divisi_id ?: null,
            'induk' => $request->induk ?: null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Satuan kerja berhasil ditambahkan.',
        ]);
    }

    public function satuanKerjaShow(Request $request, string $userId, string $id)
    {
        $wu = WorkUnit::with('divisi')->findOrFail($id);

        return response()->json(['success' => true, 'data' => $wu]);
    }

    public function satuanKerjaUpdate(Request $request, string $userId, string $id)
    {
        $satuanKerja = WorkUnit::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'divisi_id' => 'nullable|exists:divisis,id',
            'induk' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $satuanKerja->update([
            'name' => $request->name,
            'divisi_id' => $request->divisi_id ?: null,
            'induk' => $request->induk ?: null,
            'is_active' => $request->boolean('is_active', $satuanKerja->is_active),
        ]);

        return redirect()->route('user.master-data.satuan-kerja.index', ['userId' => $userId])
            ->with('success', 'Satuan kerja berhasil diperbarui.');
    }

    public function satuanKerjaDestroy(Request $request, string $userId, string $id)
    {
        $satuanKerja = WorkUnit::findOrFail($id);

        $hasGtk = DB::table('gtk_work_unit')->where('work_unit_id', $id)->exists();
        if ($hasGtk) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus satuan kerja yang memiliki GTK.',
            ], 400);
        }

        $satuanKerja->delete();

        return response()->json([
            'success' => true,
            'message' => 'Satuan kerja berhasil dihapus.',
        ]);
    }

    // ============================================================
    // SATUAN KERJA API (for JS fetch from master-data view)
    // ============================================================
    public function satuanKerjaGenerateCode(Request $request, string $userId)
    {
        $request->validate([
            'divisi_id' => 'nullable|exists:divisis,id',
            'induk' => 'nullable|string|max:50',
        ]);

        $parentId = $request->induk === '' ? null : $request->induk;
        $code = WorkUnit::generateUniqueCode($request->divisi_id, $parentId);

        return response()->json(['success' => true, 'code' => $code]);
    }

    public function satuanKerjaBulkDestroy(Request $request, string $userId)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|exists:work_units,id',
        ]);

        foreach ($request->ids as $id) {
            $wu = WorkUnit::find($id);
            if (! $wu) {
                continue;
            }
            if (DB::table('gtk_work_unit')->where('work_unit_id', $id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => "Tidak dapat menghapus '{$wu->name}' — masih memiliki GTK",
                ], 400);
            }
        }

        $deleted = WorkUnit::whereIn('id', $request->ids)->delete();

        return response()->json(['success' => true, 'message' => "$deleted satuan kerja dihapus."]);
    }

    public function satuanKerjaToggleStatus(Request $request, string $userId, string $id)
    {
        $wu = WorkUnit::findOrFail($id);
        $wu->update(['is_active' => ! $wu->is_active]);
        $status = $wu->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json(['success' => true, 'message' => "Satuan kerja berhasil $status"]);
    }
}
