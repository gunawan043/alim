<?php

namespace App\Http\Controllers\Perpustakaan;

use App\Http\Controllers\Controller;
use App\Models\LibraryGenre;
use App\Services\LibraryAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LibraryGenreController extends Controller
{
    public function __construct(private readonly LibraryAccess $access) {}

    public function index(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $schoolId = $request->attributes->get('schoolContextId');
        $genres = LibraryGenre::query()
            ->where(fn ($q) => $q->whereNull('school_id')->when($schoolId, fn ($q2) => $q2->orWhere('school_id', $schoolId)))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($inner) => $inner->where('name', 'like', '%'.$request->search.'%')->orWhere('code', 'like', '%'.$request->search.'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->orderBy('name')->get();

        $stats = ['total' => $genres->count(), 'active' => $genres->where('is_active', true)->count(), 'inactive' => $genres->where('is_active', false)->count()];

        return view('perpustakaan.genres.index', compact('userId', 'genres', 'schoolId', 'stats'));
    }

    public function store(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'code' => 'nullable|string|max:30',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
        ]);
        LibraryGenre::create([
            'school_id' => $request->attributes->get('schoolContextId'),
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Genre ditambahkan.');
    }

    public function update(Request $request, string $userId, string $id)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $genre = LibraryGenre::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'code' => 'nullable|string|max:30',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
        ]);
        $genre->update([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Genre diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $genre = LibraryGenre::findOrFail($id);
        abort_if($genre->titles()->exists(), 422, 'Genre masih dipakai judul buku.');
        $genre->delete();

        return back()->with('success', 'Genre dihapus.');
    }
}
