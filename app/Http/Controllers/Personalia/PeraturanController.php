<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use App\Models\Peraturan;
use App\Models\PeraturanKategori;
use App\Models\PeraturanViolation;
use App\Models\GtkProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PeraturanController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $query = Peraturan::with(['kategori', 'pembuat'])
            ->when($request->get('kategori_id'), fn($q, $k) => $q->where('kategori_id', $k))
            ->when($request->get('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->get('q'), fn($q, $t) => $q->where('judul', 'like', "%$t%"));

        $dokumens = $query->orderBy('tanggal_berlaku', 'desc')->paginate(20);
        $kategoris = PeraturanKategori::where('is_active', true)->orderBy('urutan')->get();

        $stats = [
            'total_dokumen'       => Peraturan::count(),
            'aktif'               => Peraturan::where('status', 'aktif')->count(),
            'draft'               => Peraturan::where('status', 'draft')->count(),
            'violations_this_month' => PeraturanViolation::whereMonth('tanggal', now()->month)
                ->whereYear('tanggal', now()->year)->count(),
        ];

        return view('personalia.peraturan.index', compact('userId', 'dokumens', 'kategoris', 'stats'));
    }

    public function create(Request $request, string $userId)
    {
        $kategoris = PeraturanKategori::where('is_active', true)->orderBy('urutan')->get();
        $gtkList = GtkProfile::with('user')->orderBy('nama')->get();

        return view('personalia.peraturan.create', compact('userId', 'kategoris', 'gtkList'));
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'kategori_id'     => 'nullable|uuid|exists:peraturan_kategoris,id',
            'judul'           => 'required|string|max:200',
            'nomor'           => 'nullable|string|max:50',
            'versi'           => 'nullable|string|max:20',
            'tanggal_ditetapkan' => 'nullable|date',
            'tanggal_berlaku' => 'nullable|date',
            'deskripsi'       => 'nullable|string',
            'file'            => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
            'status'          => 'required|in:draft,aktif,diarsipkan',
            'visibility'      => 'required|in:all,gtk,personalia,management',
        ]);

        DB::beginTransaction();
        try {
            $filePath = null;
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filePath = 'peraturan/dokumen/' . Str::uuid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('', $filePath, 'public');
            }

            Peraturan::create([
                'kategori_id'        => $validated['kategori_id'],
                'judul'              => $validated['judul'],
                'nomor'              => $validated['nomor'] ?? null,
                'versi'              => $validated['versi'] ?? null,
                'tanggal_ditetapkan' => $validated['tanggal_ditetapkan'] ?? null,
                'tanggal_berlaku'    => $validated['tanggal_berlaku'] ?? null,
                'deskripsi'          => $validated['deskripsi'] ?? null,
                'file_path'          => $filePath,
                'status'             => $validated['status'],
                'visibility'         => $validated['visibility'],
                'dibuat_oleh'        => Auth::id(),
            ]);

            DB::commit();
            return redirect()->route('user.peraturan.index', $userId)
                ->with('success', 'Dokumen peraturan berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan dokumen: ' . $e->getMessage());
        }
    }

    public function show(Request $request, string $userId, string $id)
    {
        $dokumen = Peraturan::with(['kategori', 'pembuat', 'readLogs.user'])
            ->findOrFail($id);

        $readLogs = $dokumen->readLogs()->orderBy('read_at', 'desc')->limit(10)->get();
        $totalBaca = $readLogs->count();

        return view('personalia.peraturan.show', compact('userId', 'dokumen', 'readLogs', 'totalBaca'));
    }

    public function edit(Request $request, string $userId, string $id)
    {
        $dokumen = Peraturan::with('kategori')->findOrFail($id);
        $kategoris = PeraturanKategori::where('is_active', true)->orderBy('urutan')->get();

        return view('personalia.peraturan.edit', compact('userId', 'dokumen', 'kategoris'));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $dokumen = Peraturan::findOrFail($id);

        $validated = $request->validate([
            'kategori_id'     => 'nullable|uuid|exists:peraturan_kategoris,id',
            'judul'           => 'required|string|max:200',
            'nomor'           => 'nullable|string|max:50',
            'versi'           => 'nullable|string|max:20',
            'tanggal_ditetapkan' => 'nullable|date',
            'tanggal_berlaku' => 'nullable|date',
            'deskripsi'       => 'nullable|string',
            'file'            => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
            'status'          => 'required|in:draft,aktif,diarsipkan',
            'visibility'      => 'required|in:all,gtk,personalia,management',
        ]);

        DB::beginTransaction();
        try {
            $filePath = $dokumen->file_path;
            if ($request->hasFile('file')) {
                if ($dokumen->file_path) {
                    Storage::disk('public')->delete($dokumen->file_path);
                }
                $file = $request->file('file');
                $filePath = 'peraturan/dokumen/' . Str::uuid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('', $filePath, 'public');
            }

            $dokumen->update([
                'kategori_id'        => $validated['kategori_id'],
                'judul'              => $validated['judul'],
                'nomor'              => $validated['nomor'] ?? null,
                'versi'              => $validated['versi'] ?? null,
                'tanggal_ditetapkan' => $validated['tanggal_ditetapkan'] ?? null,
                'tanggal_berlaku'    => $validated['tanggal_berlaku'] ?? null,
                'deskripsi'          => $validated['deskripsi'] ?? null,
                'file_path'          => $filePath,
                'status'             => $validated['status'],
                'visibility'         => $validated['visibility'],
            ]);

            DB::commit();
            return redirect()->route('user.peraturan.index', $userId)
                ->with('success', 'Dokumen peraturan berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui dokumen: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $dokumen = Peraturan::findOrFail($id);

        DB::beginTransaction();
        try {
            if ($dokumen->file_path) {
                Storage::disk('public')->delete($dokumen->file_path);
            }
            $dokumen->delete();
            DB::commit();
            return redirect()->route('user.peraturan.index', $userId)
                ->with('success', 'Dokumen berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus dokumen: ' . $e->getMessage());
        }
    }

    public function kategori(Request $request, string $userId)
    {
        $kategoris = PeraturanKategori::withCount('dokumens')
            ->orderBy('urutan')
            ->get();

        return view('personalia.peraturan.kategori', compact('userId', 'kategoris'));
    }

    public function kategoriStore(Request $request, string $userId)
    {
        $validated = $request->validate([
            'nama'       => 'required|string|max:100',
            'deskripsi'  => 'nullable|string',
            'warna'      => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'urutan'     => 'nullable|integer',
            'is_active'  => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            PeraturanKategori::create([
                'nama'       => $validated['nama'],
                'deskripsi'  => $validated['deskripsi'] ?? null,
                'warna'      => $validated['warna'] ?? '#6c757d',
                'urutan'     => $validated['urutan'] ?? 0,
                'is_active'  => $request->boolean('is_active'),
            ]);
            DB::commit();
            return redirect()->back()->with('success', 'Kategori berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan kategori: ' . $e->getMessage());
        }
    }

    public function kategoriUpdate(Request $request, string $userId, string $id)
    {
        $kategori = PeraturanKategori::findOrFail($id);

        $validated = $request->validate([
            'nama'       => 'required|string|max:100',
            'deskripsi'  => 'nullable|string',
            'warna'      => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'urutan'     => 'nullable|integer',
            'is_active'  => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $kategori->update([
                'nama'       => $validated['nama'],
                'deskripsi'  => $validated['deskripsi'] ?? null,
                'warna'      => $validated['warna'] ?? $kategori->warna,
                'urutan'     => $validated['urutan'] ?? $kategori->urutan,
                'is_active'  => $request->boolean('is_active'),
            ]);
            DB::commit();
            return redirect()->back()->with('success', 'Kategori berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui kategori: ' . $e->getMessage());
        }
    }

    public function kategoriDestroy(Request $request, string $userId, string $id)
    {
        $kategori = PeraturanKategori::findOrFail($id);

        $hasDocuments = Peraturan::where('kategori_id', $id)->exists();
        if ($hasDocuments) {
            return redirect()->back()->with('error', 'Kategori tidak bisa dihapus karena masih memiliki dokumen.');
        }

        DB::beginTransaction();
        try {
            $kategori->delete();
            DB::commit();
            return redirect()->back()->with('success', 'Kategori berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus kategori: ' . $e->getMessage());
        }
    }

    public function violation(Request $request, string $userId)
    {
        $query = PeraturanViolation::with(['gtk.user', 'pembuat'])
            ->when($request->get('gtk_id'), fn($q, $g) => $q->where('gtk_id', $g))
            ->when($request->get('jenis'), fn($q, $j) => $q->where('jenis', $j))
            ->when($request->get('tingkat'), fn($q, $t) => $q->where('tingkat', $t))
            ->when($request->get('bulan'), fn($q, $b) => $q->whereMonth('tanggal', Carbon::parse($b)->month)
                ->whereYear('tanggal', Carbon::parse($b)->year));

        $violations = $query->orderBy('tanggal', 'desc')->paginate(20);
        $gtkList = GtkProfile::with('user')->orderBy('nama')->get();

        $stats = [
            'active_violations'  => PeraturanViolation::where('status', 'aktif')->count(),
            'by_tingkat'         => [
                'ringan' => PeraturanViolation::where('tingkat', 'ringan')->where('status', 'aktif')->count(),
                'sedang' => PeraturanViolation::where('tingkat', 'sedang')->where('status', 'aktif')->count(),
                'berat'  => PeraturanViolation::where('tingkat', 'berat')->where('status', 'aktif')->count(),
            ],
            'recent_violations'  => PeraturanViolation::with('gtk')
                ->whereMonth('tanggal', now()->month)
                ->whereYear('tanggal', now()->year)
                ->count(),
        ];

        return view('personalia.peraturan.violation', compact('userId', 'violations', 'gtkList', 'stats'));
    }

    public function violationStore(Request $request, string $userId)
    {
        $validated = $request->validate([
            'gtk_id'         => 'required|uuid|exists:gtk_profiles,id',
            'jenis'          => 'required|in:teguran_lisan,teguran_tulisan,sp1,sp2,sp3,other',
            'tingkat'        => 'required|in:ringan,sedang,berat',
            'judul'          => 'required|string|max:200',
            'deskripsi'      => 'nullable|string',
            'sanksi'         => 'nullable|string',
            'tanggal'        => 'required|date',
            'masa_berlaku'   => 'nullable|integer|min:1',
            'bukti_path'     => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'status'         => 'required|in:aktif,selesai,diarsipkan',
        ]);

        DB::beginTransaction();
        try {
            $buktiPath = null;
            if ($request->hasFile('bukti_path')) {
                $file = $request->file('bukti_path');
                $buktiPath = 'peraturan/dokumen/' . Str::uuid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('', $buktiPath, 'public');
            }

            PeraturanViolation::create([
                'gtk_id'        => $validated['gtk_id'],
                'jenis'         => $validated['jenis'],
                'tingkat'       => $validated['tingkat'],
                'judul'         => $validated['judul'],
                'deskripsi'     => $validated['deskripsi'] ?? null,
                'sanksi'        => $validated['sanksi'] ?? null,
                'tanggal'       => $validated['tanggal'],
                'masa_berlaku'  => $validated['masa_berlaku'] ?? null,
                'bukti_path'    => $buktiPath,
                'status'        => $validated['status'],
                'dibuat_oleh'   => Auth::id(),
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Pelanggaran berhasil dicatat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan pelanggaran: ' . $e->getMessage());
        }
    }

    public function violationUpdate(Request $request, string $userId, string $id)
    {
        $violation = PeraturanViolation::findOrFail($id);

        $validated = $request->validate([
            'gtk_id'         => 'required|uuid|exists:gtk_profiles,id',
            'jenis'          => 'required|in:teguran_lisan,teguran_tulisan,sp1,sp2,sp3,other',
            'tingkat'        => 'required|in:ringan,sedang,berat',
            'judul'          => 'required|string|max:200',
            'deskripsi'      => 'nullable|string',
            'sanksi'         => 'nullable|string',
            'tanggal'        => 'required|date',
            'masa_berlaku'   => 'nullable|integer|min:1',
            'bukti_path'     => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'status'         => 'required|in:aktif,selesai,diarsipkan',
        ]);

        DB::beginTransaction();
        try {
            $buktiPath = $violation->bukti_path;
            if ($request->hasFile('bukti_path')) {
                if ($violation->bukti_path) {
                    Storage::disk('public')->delete($violation->bukti_path);
                }
                $file = $request->file('bukti_path');
                $buktiPath = 'peraturan/dokumen/' . Str::uuid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('', $buktiPath, 'public');
            }

            $violation->update([
                'gtk_id'       => $validated['gtk_id'],
                'jenis'        => $validated['jenis'],
                'tingkat'      => $validated['tingkat'],
                'judul'        => $validated['judul'],
                'deskripsi'    => $validated['deskripsi'] ?? null,
                'sanksi'       => $validated['sanksi'] ?? null,
                'tanggal'      => $validated['tanggal'],
                'masa_berlaku' => $validated['masa_berlaku'] ?? null,
                'bukti_path'   => $buktiPath,
                'status'       => $validated['status'],
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Pelanggaran berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui pelanggaran: ' . $e->getMessage());
        }
    }

    public function violationDestroy(Request $request, string $userId, string $id)
    {
        $violation = PeraturanViolation::findOrFail($id);

        DB::beginTransaction();
        try {
            if ($violation->bukti_path) {
                Storage::disk('public')->delete($violation->bukti_path);
            }
            $violation->delete();
            DB::commit();
            return redirect()->back()->with('success', 'Pelanggaran berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus pelanggaran: ' . $e->getMessage());
        }
    }

    public function datatable(Request $request, string $userId)
    {
        $query = Peraturan::with(['kategori', 'pembuat'])
            ->when($request->get('kategori_id'), fn($q, $k) => $q->where('kategori_id', $k))
            ->when($request->get('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->get('q'), fn($q, $t) => $q->where('judul', 'like', "%$t%"));

        return datatables()->of($query->orderBy('tanggal_berlaku', 'desc'))
            ->addColumn('judul', fn($r) => '<a href="' . route('user.peraturan.show', [$userId, $r->id]) . '">' . e($r->judul) . '</a>')
            ->addColumn('kategori', fn($r) => $r->kategori?->nama ?? '-')
            ->addColumn('nomor', fn($r) => $r->nomor ?? '-')
            ->addColumn('versi', fn($r) => $r->versi ?? '-')
            ->addColumn('tanggal_ditetapkan', fn($r) => $r->tanggal_ditetapkan ? $r->tanggal_ditetapkan->format('d/m/Y') : '-')
            ->addColumn('tanggal_berlaku', fn($r) => $r->tanggal_berlaku ? $r->tanggal_berlaku->format('d/m/Y') : '-')
            ->addColumn('visibility', fn($r) => ucfirst($r->visibility))
            ->addColumn('downloaded_count', fn($r) => $r->downloaded_count)
            ->addColumn('status_badge', fn($r) => match ($r->status) {
                'aktif'      => '<span class="badge bg-success-subtle text-success">Aktif</span>',
                'draft'      => '<span class="badge bg-warning-subtle text-warning">Draft</span>',
                'diarsipkan' => '<span class="badge bg-secondary-subtle text-secondary">Diarsipkan</span>',
                default      => '<span class="badge bg-secondary-subtle">-</span>',
            })
            ->rawColumns(['judul', 'status_badge'])
            ->make(true);
    }

    public function acknowledge(Request $request, string $userId, string $id)
    {
        $dokumen = Peraturan::findOrFail($id);

        $exists = $dokumen->readLogs()
            ->where('user_id', $userId)
            ->exists();

        if (! $exists) {
            $dokumen->readLogs()->create([
                'user_id'    => $userId,
                'read_at'    => now(),
                'ip_address' => $request->ip(),
            ]);
        }

        return redirect()->back()->with('success', 'Anda telah membaca dan menyetujui peraturan ini.');
    }
}