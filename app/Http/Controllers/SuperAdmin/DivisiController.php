<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Divisi;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DivisiController extends Controller
{
    public function index(Request $request)
    {
        $query = Divisi::query()->orderBy('nama');

        if ($request->filled('search')) {
            $query->where('nama', 'like', "%{$request->search}%")
                ->orWhere('kode', 'like', "%{$request->search}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $divisiList = $query->withCount('dokumenIso')->paginate(20)->withQueryString();

        return view('super-admin.divisi.index', compact('divisiList'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:150',
            'kode' => 'required|string|max:30|unique:divisis,kode',
            'deskripsi' => 'nullable|string|max:500',
            'is_active' => 'nullable|in:0,1',
        ]);

        $data['id'] = (string) Str::uuid();
        $data['is_active'] = $request->boolean('is_active', true);
        Divisi::create($data);

        return redirect()->route('system.sa.divisi.index')
            ->with('success', 'Divisi berhasil ditambahkan.');
    }

    public function update(Request $request, string $userId, string $id)
    {
        $divisi = Divisi::findOrFail($id);

        $data = $request->validate([
            'nama' => 'required|string|max:150',
            'kode' => 'required|string|max:30|unique:divisis,kode,'.$id,
            'deskripsi' => 'nullable|string|max:500',
            'is_active' => 'nullable|in:0,1',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $divisi->update($data);

        return redirect()->route('system.sa.divisi.index')
            ->with('success', 'Divisi berhasil diperbarui.');
    }

    public function destroy(string $userId, string $id)
    {
        $divisi = Divisi::findOrFail($id);

        if ($divisi->dokumenIso()->exists()) {
            return back()->with('error', 'Divisi tidak dapat dihapus karena masih memiliki dokumen ISO.');
        }

        $divisi->delete();

        return redirect()->route('system.sa.divisi.index')
            ->with('success', 'Divisi berhasil dihapus.');
    }
}
