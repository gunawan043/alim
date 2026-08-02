<?php

namespace App\Http\Controllers;

use App\Models\BoardingRegulation;
use Illuminate\Http\Request;

class BoardingRegulationController extends Controller
{
    public function index(Request $request)
    {
        $regulations = BoardingRegulation::with(['category'])->orderBy('name')->paginate(20);

        return view('dormitory.regulations.index', compact('regulations'));
    }

    public function create(Request $request)
    {
        $categories = \App\Models\RegulationCategory::all();
        $userId = $request->route('userId');

        return view('dormitory.regulations.create', compact('categories', 'userId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:regulation_categories,id',
            'content' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $regulation = BoardingRegulation::create($validated);

        return redirect()->route('user.boarding-regulations.index')
            ->with('success', 'Peraturan asrama berhasil dibuat.');
    }

    public function show(Request $request, string $id)
    {
        $regulation = BoardingRegulation::with(['category'])->findOrFail($id);
        $userId = $request->route('userId');

        return view('dormitory.regulations.show', compact('regulation', 'userId'));
    }

    public function edit(Request $request, string $id)
    {
        $regulation = BoardingRegulation::findOrFail($id);
        $categories = \App\Models\RegulationCategory::all();
        $userId = $request->route('userId');

        return view('dormitory.regulations.edit', compact('regulation', 'categories', 'userId'));
    }

    public function update(Request $request, string $id)
    {
        $regulation = BoardingRegulation::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:regulation_categories,id',
            'content' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $regulation->update($validated);

        return redirect()->route('user.boarding-regulations.index')
            ->with('success', 'Peraturan asrama berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $regulation = BoardingRegulation::findOrFail($id);
        $regulation->delete();

        return redirect()->route('user.boarding-regulations.index')
            ->with('success', 'Peraturan asrama berhasil dihapus.');
    }

    public function publish(Request $request, string $id)
    {
        $regulation = BoardingRegulation::findOrFail($id);
        $regulation->update(['is_active' => true]);

        return back()->with('success', 'Peraturan berhasil dipublikasikan.');
    }

    public function archive(Request $request, string $id)
    {
        $regulation = BoardingRegulation::findOrFail($id);
        $regulation->update(['is_active' => false]);

        return back()->with('success', 'Peraturan berhasil diarsipkan.');
    }

    public function export(Request $request)
    {
        $regulations = BoardingRegulation::with('category')->get();

        return response()->view('dormitory.regulations.export', compact('regulations'))
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'attachment; filename="peraturan-asrama-' . date('Y-m-d') . '.html"');
    }

    public function print(Request $request, string $id)
    {
        $regulation = BoardingRegulation::with(['category'])->findOrFail($id);

        return view('dormitory.regulations.print', compact('regulation'));
    }
}
