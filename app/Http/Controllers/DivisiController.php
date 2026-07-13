<?php

namespace App\Http\Controllers;

use App\Models\Divisi;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DivisiController extends Controller
{
    public function index(Request $request)
    {
        $query = Divisi::query()->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $query->where('nama', 'like', "%{$request->search}%")
                ->orWhere('kode', 'like', "%{$request->search}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $divisiList = $query->withCount('dokumenIso')->paginate(20)->withQueryString();
        $isSuperAdmin = canPermission('super-admin-only');

        return view('divisi.index', compact('divisiList', 'isSuperAdmin'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:150',
            'kode' => 'required|string|max:30|unique:divisis,kode',
            'deskripsi' => 'nullable|string',
            'is_active' => 'nullable|in:0,1',
        ]);

        $data['id'] = Str::uuid();
        $data['is_active'] = $request->boolean('is_active', true);
        Divisi::create($data);

        return redirect()->back()->with('success', 'Divisi berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $divisi = Divisi::findOrFail($id);

        $data = $request->validate([
            'nama' => 'required|string|max:150',
            'kode' => 'required|string|max:30|unique:divisis,kode,'.$id,
            'deskripsi' => 'nullable|string',
            'is_active' => 'nullable|in:0,1',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $divisi->update($data);

        return redirect()->back()->with('success', 'Divisi berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $divisi = Divisi::findOrFail($id);

        if ($divisi->dokumen_iso_count > 0) {
            return redirect()->back()->with('error', 'Divisi tidak dapat dihapus karena masih memiliki dokumen ISO.');
        }

        $divisi->delete();

        return redirect()->back()->with('success', 'Divisi berhasil dihapus.');
    }
}
