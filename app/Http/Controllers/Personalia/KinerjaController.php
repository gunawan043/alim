<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class KinerjaController extends Controller
{
    public function index(Request $request, string $userId)
    {
        return view('personalia.kinerja.index', compact('userId'));
    }

    public function create(Request $request, string $userId)
    {
        return view('personalia.kinerja.create', compact('userId'));
    }

    public function store(Request $request, string $userId)
    {
        // TODO: Implement store logic
        return redirect()->back()->with('success', 'Penilaian kinerja berhasil disimpan.');
    }

    public function edit(Request $request, string $userId, string $id)
    {
        return view('personalia.kinerja.edit', compact('userId', 'id'));
    }

    public function update(Request $request, string $userId, string $id)
    {
        // TODO: Implement update logic
        return redirect()->back()->with('success', 'Penilaian kinerja berhasil diupdate.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        // TODO: Implement destroy logic
        return redirect()->back()->with('success', 'Penilaian kinerja berhasil dihapus.');
    }

    public function periode(Request $request, string $userId)
    {
        return view('personalia.kinerja.periode', compact('userId'));
    }

    public function indikator(Request $request, string $userId)
    {
        return view('personalia.kinerja.indikator', compact('userId'));
    }

    public function reward(Request $request, string $userId)
    {
        return view('personalia.kinerja.reward', compact('userId'));
    }

    public function laporan(Request $request, string $userId)
    {
        return view('personalia.kinerja.laporan', compact('userId'));
    }

    public function datatable(Request $request, string $userId)
    {
        // TODO: Return JSON datatable response
        return response()->json(['data' => []]);
    }
}