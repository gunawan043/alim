<?php

namespace App\Http\Controllers\Perpustakaan;

use App\Http\Controllers\Controller;
use App\Models\LibraryGenre;
use App\Models\LibraryItem;
use App\Models\LibraryTitle;
use App\Models\LibraryUnit;
use App\Services\LibraryAccess;
use App\Services\Ljk\LjkQrRenderer;
use App\Services\Pdf\DocumentPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LibraryCatalogController extends Controller
{
    public function __construct(private readonly LibraryAccess $access) {}

    public function index(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $units = $this->access->unitsFor($request->user());
        $unit = $units->firstWhere('id', $request->input('unit_id')) ?? $units->first();
        abort_unless($unit, 404, 'Belum ada unit perpustakaan.');
        $schoolId = $unit->school_id ?: $request->attributes->get('schoolContextId');

        $titles = LibraryTitle::with('genre:id,name')->where('school_id', $schoolId)
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($s) => $s->where('title', 'like', '%'.$request->search.'%')->orWhere('author', 'like', '%'.$request->search.'%')))
            ->when($request->input('collection') === 'reference', fn ($q) => $q->whereIn('collection_type', ['kitab', 'referensi']))
            ->when($request->filled('collection') && $request->input('collection') !== 'reference', fn ($q) => $q->where('collection_type', $request->collection))
            ->orderBy('title')->limit(200)->get();
        $items = LibraryItem::with(['title:id,title,author,collection_type', 'title.genre:id,name'])
            ->where('library_unit_id', $unit->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->input('condition') === 'damaged', fn ($q) => $q->whereIn('condition', ['rusak_ringan', 'rusak_sedang', 'rusak_berat']))
            ->when($request->input('condition') === 'problem', fn ($q) => $q->where(fn ($inner) => $inner->whereIn('condition', ['rusak_ringan', 'rusak_sedang', 'rusak_berat'])->orWhere('status', LibraryItem::STATUS_LOST)))
            ->when($request->input('collection') === 'reference', fn ($q) => $q->whereHas('title', fn ($t) => $t->whereIn('collection_type', ['kitab', 'referensi'])))
            ->when($request->filled('collection') && $request->input('collection') !== 'reference', fn ($q) => $q->whereHas('title', fn ($t) => $t->where('collection_type', $request->collection)))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($s) => $s->where('inventory_code', 'like', '%'.$request->search.'%')->orWhereHas('title', fn ($t) => $t->where('title', 'like', '%'.$request->search.'%'))))
            ->orderByDesc('created_at')->limit(200)->get();
        $genres = LibraryGenre::whereNull('school_id')->orWhere('school_id', $schoolId)->orderBy('name')->get();

        return view('perpustakaan.catalog.index', compact('userId', 'units', 'unit', 'titles', 'items', 'genres'));
    }

    public function racks(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $units = $this->access->unitsFor($request->user());
        $unit = $units->firstWhere('id', $request->input('unit_id')) ?? $units->first();
        abort_unless($unit, 404, 'Belum ada unit perpustakaan.');
        $shelves = LibraryItem::where('library_unit_id', $unit->id)
            ->whereNotNull('shelf_location')->where('shelf_location', '!=', '')
            ->selectRaw('shelf_location, count(*) as total, sum(case when status = ? then 1 else 0 end) as available', [LibraryItem::STATUS_AVAILABLE])
            ->groupBy('shelf_location')->orderBy('shelf_location')->get();
        $items = LibraryItem::with('title:id,title,author')->where('library_unit_id', $unit->id)
            ->when($request->filled('shelf'), fn ($q) => $q->where('shelf_location', $request->shelf))
            ->orderBy('shelf_location')->orderBy('inventory_code')->limit(500)->get();
        $unassigned = LibraryItem::where('library_unit_id', $unit->id)->where(fn ($q) => $q->whereNull('shelf_location')->orWhere('shelf_location', ''))->count();

        return view('perpustakaan.racks.index', compact('userId', 'units', 'unit', 'shelves', 'items', 'unassigned'));
    }

    public function moveItem(Request $request, string $userId, string $id)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $item = LibraryItem::with('unit')->findOrFail($id);
        abort_unless($this->access->canAccessUnit($request->user(), $item->unit), 403);
        $validated = $request->validate(['shelf_location' => 'required|string|max:191']);
        $item->update(['shelf_location' => $validated['shelf_location']]);

        return back()->with('success', 'Lokasi rak diperbarui.');
    }

    public function storeTitle(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'nullable|string|max:191',
            'publisher' => 'nullable|string|max:191',
            'publication_year' => 'nullable|integer|min:1900|max:2100',
            'genre_id' => 'nullable|exists:library_genres,id',
            'collection_type' => 'nullable|in:umum,fiksi,nonfiksi,pelajaran,kitab,referensi',
            'isbn' => 'nullable|string|max:30',
            'description' => 'nullable|string|max:5000',
            'is_active' => 'boolean',
        ]);
        $validated['school_id'] = $request->attributes->get('schoolContextId');
        $validated['is_active'] = $request->boolean('is_active', true);
        LibraryTitle::create($validated);

        return back()->with('success', 'Judul buku ditambahkan.');
    }

    public function updateTitle(Request $request, string $userId, string $id)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $title = LibraryTitle::findOrFail($id);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'nullable|string|max:191',
            'publisher' => 'nullable|string|max:191',
            'publication_year' => 'nullable|integer|min:1900|max:2100',
            'genre_id' => 'nullable|exists:library_genres,id',
            'collection_type' => 'nullable|in:umum,fiksi,nonfiksi,pelajaran,kitab,referensi',
            'isbn' => 'nullable|string|max:30',
            'description' => 'nullable|string|max:5000',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        $title->update($validated);

        return back()->with('success', 'Judul buku diperbarui.');
    }

    public function destroyTitle(Request $request, string $userId, string $id)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $title = LibraryTitle::findOrFail($id);
        abort_if($title->items()->where('status', LibraryItem::STATUS_ON_LOAN)->exists(), 422, 'Judul masih memiliki eksemplar yang dipinjam.');
        $title->delete();

        return back()->with('success', 'Judul buku diarsipkan.');
    }

    public function storeItem(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $validated = $request->validate([
            'library_title_id' => 'required|exists:library_titles,id',
            'library_unit_id' => 'required|exists:library_units,id',
            'inventory_code' => 'nullable|string|max:80',
            'shelf_location' => 'nullable|string|max:191',
            'condition' => 'required|in:baik,rusak_ringan,rusak_sedang,rusak_berat',
            'status' => 'required|in:available,maintenance,lost,withdrawn',
            'acquisition_date' => 'nullable|date',
            'price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);
        $unit = LibraryUnit::findOrFail($validated['library_unit_id']);
        abort_unless($this->access->canAccessUnit($request->user(), $unit), 403);
        $code = trim((string) ($validated['inventory_code'] ?? ''));
        if ($code === '') {
            $prefix = strtoupper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $unit->code), 0, 5));
            $code = $prefix.'-'.str_pad((string) (LibraryItem::where('library_unit_id', $unit->id)->count() + 1), 4, '0', STR_PAD_LEFT);
            while (LibraryItem::where('inventory_code', $code)->exists()) {
                $code = $prefix.'-'.str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
            }
        }
        abort_if(LibraryItem::where('inventory_code', $code)->exists(), 422, 'Kode inventaris sudah dipakai.');
        LibraryItem::create(array_merge($validated, ['inventory_code' => $code, 'library_unit_id' => $unit->id]));

        return back()->with('success', 'Eksemplar ditambahkan.');
    }

    public function updateItem(Request $request, string $userId, string $id)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $item = LibraryItem::with('unit')->findOrFail($id);
        abort_unless($this->access->canAccessUnit($request->user(), $item->unit), 403);
        $validated = $request->validate([
            'shelf_location' => 'nullable|string|max:191',
            'condition' => 'required|in:baik,rusak_ringan,rusak_sedang,rusak_berat',
            'status' => 'required|in:available,maintenance,lost,withdrawn',
            'notes' => 'nullable|string|max:2000',
        ]);
        $item->update($validated);

        return back()->with('success', 'Eksemplar diperbarui.');
    }

    public function destroyItem(Request $request, string $userId, string $id)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $item = LibraryItem::with('unit')->findOrFail($id);
        abort_unless($this->access->canAccessUnit($request->user(), $item->unit), 403);
        abort_if($item->status === LibraryItem::STATUS_ON_LOAN, 422, 'Eksemplar sedang dipinjam.');
        $item->update(['status' => LibraryItem::STATUS_WITHDRAWN]);

        return back()->with('success', 'Eksemplar ditarik dari koleksi aktif.');
    }

    public function printLabels(Request $request, string $userId)
    {
        abort_unless($this->access->canManage($request->user()), 403);
        $unit = LibraryUnit::findOrFail($request->input('unit_id'));
        abort_unless($this->access->canAccessUnit($request->user(), $unit), 403);
        $itemIds = collect($request->input('item_ids', []))->filter()->unique()->values();
        $items = LibraryItem::with('title:id,title')->where('library_unit_id', $unit->id)
            ->when($itemIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $itemIds))
            ->orderBy('inventory_code')->limit(240)->get();
        abort_if($items->isEmpty(), 404, 'Tidak ada eksemplar untuk dicetak.');

        $qrData = $items->mapWithKeys(fn ($item) => [
            $item->id => LjkQrRenderer::pngDataUri($item->inventory_code, 220),
        ])->all();
        $items->each(fn ($item) => $item->forceFill(['qr_generated_at' => now()])->saveQuietly());

        return app(DocumentPdfService::class)->fromView(
            'perpustakaan.catalog.labels',
            compact('items', 'unit', 'qrData'),
            'label-buku-'.$unit->code,
            'portrait'
        );
    }
}
