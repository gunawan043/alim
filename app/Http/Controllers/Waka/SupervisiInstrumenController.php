<?php

declare(strict_types=1);

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\SupervisiInstrumen;
use App\Models\SupervisiInstrumenBagian;
use App\Models\SupervisiInstrumenItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Pengaturan Instrumen Supervisi: bagian, item (list yang dinilai), dan poinnya.
 *
 * Kebijakan:
 * - Instrumen template GLOBAL (school_id null) bersifat baca-saja — satuan
 *   menduplikasinya menjadi instrumen milik satuan agar tidak saling mengubah.
 * - Instrumen milik satuan hanya dapat diubah oleh pengguna bertenaga pada
 *   satuan tersebut (route sudah dibatasi peran waka/TU pada domain waka).
 */
class SupervisiInstrumenController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $instrumen = SupervisiInstrumen::query()
            ->where(function ($q) use ($schoolId) {
                $q->whereNull('school_id');
                if ($schoolId) {
                    $q->orWhere('school_id', $schoolId);
                }
            })
            ->withCount(['bagian'])
            ->orderByRaw('school_id IS NULL') // milik satuan tampil lebih dulu
            ->orderBy('jenis')
            ->get()
            ->map(function (SupervisiInstrumen $i) {
                $i->items_count = SupervisiInstrumenItem::whereIn('bagian_id', $i->bagian()->pluck('id'))->count();

                return $i;
            });

        $canManageInstrumen = $this->canManageInstrumen($request);

        return view('waka.supervisi.instrumen.index', compact('instrumen', 'canManageInstrumen'));
    }

    public function store(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        abort_unless($schoolId, 403, 'Konteks satuan pendidikan tidak ditemukan.');

        $validated = $request->validate([
            'nama' => 'required|string|max:150',
            'jenis' => 'required|in:guru_umum,guru_agama,lainnya',
            'deskripsi' => 'nullable|string|max:1000',
        ]);

        $instrumen = SupervisiInstrumen::create([
            'school_id' => $schoolId,
            'nama' => $validated['nama'],
            'jenis' => $validated['jenis'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'is_active' => true,
        ]);

        return redirect()
            ->route('waka.supervisi-instrumen.edit', $instrumen->id)
            ->with('success', 'Instrumen dibuat. Tambahkan bagian dan item yang dinilai.');
    }

    public function edit(Request $request, string $id)
    {
        $instrumen = $this->findScoped($request, $id, true);

        $instrumen->load(['bagian' => fn ($q) => $q->orderBy('urutan'), 'bagian.items', 'bagian.items.subItems']);

        $canEditInstrumen = $this->canManageInstrumen($request);

        return view('waka.supervisi.instrumen.edit', compact('instrumen', 'canEditInstrumen'));
    }

    public function duplicate(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        abort_unless($schoolId, 403, 'Konteks satuan pendidikan tidak ditemukan.');

        $source = SupervisiInstrumen::with(['bagian.items', 'bagian.items.subItems'])->findOrFail($id);

        $copy = SupervisiInstrumen::create([
            'school_id' => $schoolId,
            'nama' => $source->nama.' (Salinan)',
            'jenis' => $source->jenis,
            'deskripsi' => $source->deskripsi,
            'is_active' => true,
        ]);

        foreach ($source->bagian as $bagian) {
            $bagianCopy = SupervisiInstrumenBagian::create([
                'instrumen_id' => $copy->id,
                'kode' => $bagian->kode,
                'nama' => $bagian->nama,
                'urutan' => $bagian->urutan,
                'is_active' => true,
            ]);

            foreach ($bagian->items as $item) {
                $itemCopy = SupervisiInstrumenItem::create([
                    'bagian_id' => $bagianCopy->id,
                    'teks' => $item->teks,
                    'urutan' => $item->urutan,
                    'bobot' => $item->bobot,
                    'butuh_kondisi' => $item->butuh_kondisi,
                    'is_active' => true,
                ]);

                foreach ($item->subItems as $sub) {
                    SupervisiInstrumenItem::create([
                        'bagian_id' => $bagianCopy->id,
                        'parent_item_id' => $itemCopy->id,
                        'teks' => $sub->teks,
                        'urutan' => $sub->urutan,
                        'bobot' => $sub->bobot,
                        'butuh_kondisi' => $sub->butuh_kondisi,
                        'is_active' => true,
                    ]);
                }
            }
        }

        return redirect()
            ->route('waka.supervisi-instrumen.edit', $copy->id)
            ->with('success', 'Instrumen berhasil diduplikasi ke satuan. Silakan atur item & poinnya.');
    }

    public function update(Request $request, string $id)
    {
        $instrumen = $this->findScoped($request, $id);

        $validated = $request->validate([
            'nama' => 'required|string|max:150',
            'jenis' => 'required|in:guru_umum,guru_agama,lainnya',
            'deskripsi' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $instrumen->update([
            'nama' => $validated['nama'],
            'jenis' => $validated['jenis'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Identitas instrumen diperbarui.');
    }

    // ── BAGIAN ──────────────────────────────────────────────────

    public function storeBagian(Request $request, string $id)
    {
        $instrumen = $this->findScoped($request, $id);

        $validated = $request->validate([
            'kode' => 'nullable|string|max:10',
            'nama' => 'required|string|max:190',
            'urutan' => 'nullable|integer|min:0|max:99',
        ]);

        SupervisiInstrumenBagian::create([
            'instrumen_id' => $instrumen->id,
            'kode' => $validated['kode'] ?? null,
            'nama' => $validated['nama'],
            'urutan' => $validated['urutan'] ?? (SupervisiInstrumenBagian::where('instrumen_id', $instrumen->id)->max('urutan') + 1),
            'is_active' => true,
        ]);

        return back()->with('success', 'Bagian ditambahkan.');
    }

    public function updateBagian(Request $request, string $bagianId)
    {
        $bagian = SupervisiInstrumenBagian::with('instrumen')->findOrFail($bagianId);
        $this->assertEditable($request, $bagian->instrumen);

        $validated = $request->validate([
            'kode' => 'nullable|string|max:10',
            'nama' => 'required|string|max:190',
            'urutan' => 'nullable|integer|min:0|max:99',
            'is_active' => 'nullable|boolean',
        ]);

        $bagian->update([
            'kode' => $validated['kode'] ?? null,
            'nama' => $validated['nama'],
            'urutan' => $validated['urutan'] ?? $bagian->urutan,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Bagian diperbarui.');
    }

    public function destroyBagian(Request $request, string $bagianId)
    {
        $bagian = SupervisiInstrumenBagian::with('instrumen')->findOrFail($bagianId);
        $this->assertEditable($request, $bagian->instrumen);

        $bagian->delete();

        return back()->with('success', 'Bagian beserta itemnya dihapus.');
    }

    // ── ITEM (list yang dinilai + poin) ─────────────────────────

    public function storeItem(Request $request, string $bagianId)
    {
        $bagian = SupervisiInstrumenBagian::with('instrumen')->findOrFail($bagianId);
        $this->assertEditable($request, $bagian->instrumen);

        $validated = $request->validate([
            'teks' => 'required|string|max:500',
            'bobot' => 'nullable|numeric|min:0|max:100',
            'butuh_kondisi' => 'nullable|boolean',
            'parent_item_id' => 'nullable|exists:supervisi_instrumen_item,id',
        ]);

        SupervisiInstrumenItem::create([
            'bagian_id' => $bagian->id,
            'parent_item_id' => $validated['parent_item_id'] ?? null,
            'teks' => $validated['teks'],
            'urutan' => (SupervisiInstrumenItem::where('bagian_id', $bagian->id)
                ->where('parent_item_id', $validated['parent_item_id'] ?? null)
                ->max('urutan')) + 1,
            'bobot' => $validated['bobot'] ?? 4,
            'butuh_kondisi' => $request->boolean('butuh_kondisi', true),
            'is_active' => true,
        ]);

        return back()->with('success', 'Item ditambahkan.');
    }

    public function updateItem(Request $request, string $itemId)
    {
        $item = SupervisiInstrumenItem::with('bagian.instrumen')->findOrFail($itemId);
        $this->assertEditable($request, $item->bagian?->instrumen);

        $validated = $request->validate([
            'teks' => 'required|string|max:500',
            'bobot' => 'nullable|numeric|min:0|max:100',
            'butuh_kondisi' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $item->update([
            'teks' => $validated['teks'],
            'bobot' => $validated['bobot'] ?? $item->bobot,
            'butuh_kondisi' => $request->boolean('butuh_kondisi', true),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Item diperbarui.');
    }

    public function destroyItem(Request $request, string $itemId)
    {
        $item = SupervisiInstrumenItem::with('bagian.instrumen')->findOrFail($itemId);
        $this->assertEditable($request, $item->bagian?->instrumen);

        // Hapus sub-item ikut terhapus (nullOnDelete hanya memutus relasi) — hapus eksplisit.
        SupervisiInstrumenItem::where('parent_item_id', $item->id)->delete();
        $item->delete();

        return back()->with('success', 'Item dihapus.');
    }

    public function moveItem(Request $request, string $itemId)
    {
        $item = SupervisiInstrumenItem::with('bagian.instrumen')->findOrFail($itemId);
        $this->assertEditable($request, $item->bagian?->instrumen);

        $direction = $request->input('direction') === 'up' ? -1 : 1;

        $sibling = SupervisiInstrumenItem::where('bagian_id', $item->bagian_id)
            ->where('parent_item_id', $item->parent_item_id)
            ->where('urutan', $direction === -1 ? '<' : '>', $item->urutan)
            ->orderBy('urutan', $direction === -1 ? 'desc' : 'asc')
            ->first();

        if (! $sibling) {
            return back();
        }

        $swap = $item->urutan;
        $item->update(['urutan' => $sibling->urutan]);
        $sibling->update(['urutan' => $swap]);

        return back()->with('success', 'Urutan item diperbarui.');
    }

    // ── GUARD ───────────────────────────────────────────────────

    private function canManageInstrumen(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        if ((method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin())
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())) {
            return true;
        }

        $jabatan = strtoupper(trim((string) ($user->employment?->jabatan ?? '')));
        $hasJabatan = fn (string $needle): bool => str_contains($jabatan, strtoupper($needle));

        $tugas = collect();
        if (method_exists($user, 'tugasTambahan')) {
            $tugas = $tugas->merge($user->tugasTambahan()->pluck('nama_tugas'));
        }
        if (class_exists(\App\Models\OtherTeacherTask::class)) {
            $tugas = $tugas->merge(
                \App\Models\OtherTeacherTask::query()
                    ->where('teacher_id', $user->id)
                    ->where('is_active', 1)
                    ->pluck('task_name')
            );
        }
        $hasTugas = fn (string $needle): bool => $tugas
            ->filter()
            ->map(fn ($name) => strtoupper(trim((string) $name)))
            ->contains(strtoupper($needle));

        return $hasJabatan('KEPALA SATUAN PENDIDIKAN')
            || $hasJabatan('WAKIL KEPALA SATUAN PENDIDIKAN')
            || $hasJabatan('KOORDINATOR KURIKULUM')
            || $hasJabatan('TATA USAHA')
            || $hasTugas('TIM KURIKULUM');
    }

    private function findScoped(Request $request, string $id, bool $readOnly = false): SupervisiInstrumen
    {
        $instrumen = SupervisiInstrumen::findOrFail($id);

        if ($readOnly) {
            $this->assertVisible($request, $instrumen);

            return $instrumen;
        }

        $this->assertEditable($request, $instrumen);

        return $instrumen;
    }

    private function assertVisible(Request $request, ?SupervisiInstrumen $instrumen): void
    {
        abort_unless($instrumen, 404);

        $schoolId = $request->attributes->get('schoolContextId');

        // Template global boleh dilihat semua; instrumen satuan hanya satuan pemiliknya.
        abort_if($instrumen->school_id && $schoolId && $instrumen->school_id !== $schoolId, 403);
    }

    private function assertEditable(Request $request, ?SupervisiInstrumen $instrumen): void
    {
        abort_unless($instrumen, 404);

        $schoolId = $request->attributes->get('schoolContextId');

        // Template global baca-saja: duplikat dulu ke satuan.
        abort_if($instrumen->school_id === null, 403, 'Instrumen bawaan bersifat baca-saja. Duplikat ke satuan untuk mengubah.');

        abort_unless($schoolId, 403, 'Konteks satuan pendidikan tidak ditemukan.');
        abort_if($instrumen->school_id !== $schoolId, 403, 'Instrumen milik satuan pendidikan lain.');
    }
}
