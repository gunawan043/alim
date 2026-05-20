<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PelatihanController extends Controller
{
    public function index(Request $request, string $userId)
    {
        return view('personalia.pelatihan.index', compact('userId'));
    }

    public function create(Request $request, string $userId)
    {
        return view('personalia.pelatihan.create', compact('userId'));
    }

    public function store(Request $request, string $userId)
    {
        // TODO: Implement store logic
        return redirect()->back()->with('success', 'Pelatihan berhasil disimpan.');
    }

    public function edit(Request $request, string $userId, string $id)
    {
        return view('personalia.pelatihan.edit', compact('userId', 'id'));
    }

    public function update(Request $request, string $userId, string $id)
    {
        // TODO: Implement update logic
        return redirect()->back()->with('success', 'Pelatihan berhasil diupdate.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        // TODO: Implement destroy logic
        return redirect()->back()->with('success', 'Pelatihan berhasil dihapus.');
    }

    public function peserta(Request $request, string $userId)
    {
        return view('personalia.pelatihan.peserta', compact('userId'));
    }

    public function jenis(Request $request, string $userId)
    {
        return view('personalia.pelatihan.jenis', compact('userId'));
    }

    public function sertifikasi(Request $request, string $userId)
    {
        return view('personalia.pelatihan.sertifikasi', compact('userId'));
    }

    public function rekap(Request $request, string $userId)
    {
        return view('personalia.pelatihan.rekap', compact('userId'));
    }

    public function datatable(Request $request, string $userId)
    {
        // TODO: Return JSON datatable response
        return response()->json(['data' => []]);
    }
}