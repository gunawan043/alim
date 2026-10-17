<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Regulation;
use Illuminate\Http\Request;

class RegulationController extends Controller
{
    /**
     * Tampilkan daftar peraturan.
     */
    public function index(Request $request)
    {
        $query = Regulation::orderBy('bab')->orderBy('pasal');

        if ($request->search) {
            $query->where('title', 'like', '%'.$request->search.'%')
                ->orWhere('content', 'like', '%'.$request->search.'%')
                ->orWhere('pasal', 'like', '%'.$request->search.'%');
        }

        $regulations = $query->paginate(15);

        return view('admin.regulations.index', compact('regulations'));
    }

    /**
     * Halaman untuk menambahkan peraturan baru.
     */
    public function create()
    {
        return view('admin.regulations.create');
    }

    /**
     * Simpan peraturan baru ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'bab' => 'required|string|max:10',
            'pasal' => 'required|string|max:10',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'order' => 'required|integer',
        ]);

        Regulation::create($request->all());

        return redirect()->back()->with('success', 'Peraturan berhasil ditambahkan!');
    }

    /**
     * Halaman untuk mengedit peraturan.
     */
    public function edit(Regulation $regulation)
    {
        return view('admin.regulations.edit', compact('regulation'));
    }

    /**
     * Simpan perubahan peraturan.
     */
    public function update(Request $request, Regulation $regulation)
    {
        $request->validate([
            'bab' => 'required|string|max:10',
            'pasal' => 'required|string|max:10',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'order' => 'required|integer',
        ]);

        $request->merge(['updated_by' => auth()->id()]);
        $regulation->update($request->all());

        return redirect()->back()->with('success', 'Peraturan berhasil diubah!');
    }

    /**
     * Hapus peraturan.
     */
    public function destroy(Regulation $regulation)
    {
        $regulation->delete();

        return redirect()->back()->with('success', 'Peraturan berhasil dihapus!');
    }
}
