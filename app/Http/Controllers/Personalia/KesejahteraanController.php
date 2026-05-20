<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class KesejahteraanController extends Controller
{
    public function index(Request $request, string $userId)
    {
        return view('personalia.kesejahteraan.index', compact('userId'));
    }

    public function create(Request $request, string $userId)
    {
        return view('personalia.kesejahteraan.create', compact('userId'));
    }

    public function store(Request $request, string $userId)
    {
        // TODO: Implement store logic
        return redirect()->back()->with('success', 'Data kesejahteraan berhasil disimpan.');
    }

    public function edit(Request $request, string $userId, string $id)
    {
        return view('personalia.kesejahteraan.edit', compact('userId', 'id'));
    }

    public function update(Request $request, string $userId, string $id)
    {
        // TODO: Implement update logic
        return redirect()->back()->with('success', 'Data kesejahteraan berhasil diupdate.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        // TODO: Implement destroy logic
        return redirect()->back()->with('success', 'Data kesejahteraan berhasil dihapus.');
    }

    public function asuransi(Request $request, string $userId)
    {
        return view('personalia.kesejahteraan.asuransi', compact('userId'));
    }

    public function benefit(Request $request, string $userId)
    {
        return view('personalia.kesejahteraan.benefit', compact('userId'));
    }

    public function umum(Request $request, string $userId)
    {
        return view('personalia.kesejahteraan.umum', compact('userId'));
    }

    public function laporan(Request $request, string $userId)
    {
        return view('personalia.kesejahteraan.laporan', compact('userId'));
    }

    public function datatable(Request $request, string $userId)
    {
        // TODO: Return JSON datatable response
        return response()->json(['data' => []]);
    }
}