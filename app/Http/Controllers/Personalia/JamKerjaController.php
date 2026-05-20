<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class JamKerjaController extends Controller
{
    public function index(Request $request, string $userId)
    {
        return view('personalia.jam-kerja.index', compact('userId'));
    }

    public function create(Request $request, string $userId)
    {
        return view('personalia.jam-kerja.create', compact('userId'));
    }

    public function store(Request $request, string $userId)
    {
        // TODO: Implement store logic
        return redirect()->back()->with('success', 'Jam kerja berhasil disimpan.');
    }

    public function edit(Request $request, string $userId, string $id)
    {
        return view('personalia.jam-kerja.edit', compact('userId', 'id'));
    }

    public function update(Request $request, string $userId, string $id)
    {
        // TODO: Implement update logic
        return redirect()->back()->with('success', 'Jam kerja berhasil diupdate.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        // TODO: Implement destroy logic
        return redirect()->back()->with('success', 'Jam kerja berhasil dihapus.');
    }

    public function shift(Request $request, string $userId)
    {
        return view('personalia.jam-kerja.shift', compact('userId'));
    }

    public function kalender(Request $request, string $userId)
    {
        return view('personalia.jam-kerja.kalender', compact('userId'));
    }

    public function datatable(Request $request, string $userId)
    {
        // TODO: Return JSON datatable response
        return response()->json(['data' => []]);
    }
}