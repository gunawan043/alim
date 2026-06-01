<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use App\Models\Kesejahteraan;
use App\Models\KesejahteraanPenerima;
use App\Models\KesejahteraanKlaim;
use App\Models\Bpjs;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KesejahteraanController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $query = Kesejahteraan::withCount('penerima')
            ->when($request->get('jenis'), fn($q, $j) => $q->where('jenis', $j))
            ->when($request->get('status') === 'aktif', fn($q) => $q->where('is_active', true))
            ->when($request->get('status') === 'nonaktif', fn($q) => $q->where('is_active', false))
            ->when($request->get('q'), fn($q, $s) => $q->where('nama', 'like', "%$s%"));

        $programs = $query->orderBy('urutan')->paginate(20);
        return view('personalia.kesejahteraan.index', compact('userId', 'programs'));
    }

    public function create(Request $request, string $userId)
    {
        return view('personalia.kesejahteraan.create', compact('userId'));
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'nama'               => 'required|string|max:200',
            'jenis'              => 'required|in:bantuan,santunan,bpjs,klaim,fasilitas',
            'nilai_default'      => 'nullable|numeric|min:0',
            'requires_approval'  => 'boolean',
            'is_active'          => 'boolean',
            'deskripsi'          => 'nullable|string',
        ]);
        $validated['requires_approval'] = $request->boolean('requires_approval');
        $validated['is_active'] = $request->boolean('is_active', true);
        Kesejahteraan::create($validated);

        return redirect()->route('user.kesejahteraan.index', $userId)
            ->with('success', 'Program kesejahteraan berhasil disimpan.');
    }

    public function show(Request $request, string $userId, string $id)
    {
        $kes = Kesejahteraan::with(['penerima.user'])->findOrFail($id);
        return view('personalia.kesejahteraan.show', compact('userId', 'kes'));
    }

    public function edit(Request $request, string $userId, string $id)
    {
        $kes = Kesejahteraan::findOrFail($id);
        return view('personalia.kesejahteraan.edit', compact('userId', 'kes'));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $kes = Kesejahteraan::findOrFail($id);
        $validated = $request->validate([
            'nama'               => 'required|string|max:200',
            'jenis'              => 'required|in:bantuan,santunan,bpjs,klaim,fasilitas',
            'nilai_default'      => 'nullable|numeric|min:0',
            'requires_approval'  => 'boolean',
            'is_active'          => 'boolean',
            'deskripsi'          => 'nullable|string',
        ]);
        $validated['requires_approval'] = $request->boolean('requires_approval');
        $validated['is_active'] = $request->boolean('is_active', true);
        $kes->update($validated);

        return redirect()->route('user.kesejahteraan.index', $userId)
            ->with('success', 'Program kesejahteraan berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        Kesejahteraan::findOrFail($id)->delete();
        return redirect()->route('user.kesejahteraan.index', $userId)
            ->with('success', 'Program kesejahteraan berhasil dihapus.');
    }

    public function asuransi(Request $request, string $userId)
    {
        $bpjs = Bpjs::with('user')
            ->when($request->get('jenis'), fn($q, $j) => $q->where('jenis_bpjs', $j))
            ->when($request->get('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->get('q'), fn($q, $s) => $q->whereHas('user', fn($qq) => $qq->where('name', 'like', "%$s%")))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('personalia.kesejahteraan.asuransi', compact('userId', 'bpjs'));
    }

    public function benefit(Request $request, string $userId)
    {
        $penerima = KesejahteraanPenerima::with(['user', 'kesejahteraan'])
            ->when($request->get('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->get('q'), fn($q, $s) => $q->whereHas('user', fn($qq) => $qq->where('name', 'like', "%$s%")))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('personalia.kesejahteraan.benefit', compact('userId', 'penerima'));
    }

    public function umum(Request $request, string $userId)
    {
        $kesejahteraans = Kesejahteraan::orderBy('urutan')->get();
        return view('personalia.kesejahteraan.umum', compact('userId', 'kesejahteraans'));
    }

    public function laporan(Request $request, string $userId)
    {
        $byJenis = KesejahteraanPenerima::with('kesejahteraan')
            ->selectRaw('kesejahteraan_id, status, COUNT(*) as total')
            ->groupBy('kesejahteraan_id', 'status')
            ->get()
            ->groupBy('kesejahteraan_id');

        $recent = KesejahteraanKlaim::with(['user', 'kesejahteraan'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('personalia.kesejahteraan.laporan', compact('userId', 'byJenis', 'recent'));
    }

    public function datatable(Request $request, string $userId)
    {
        $query = Kesejahteraan::withCount('penerima')
            ->when($request->get('jenis'), fn($q, $j) => $q->where('jenis', $j));

        return datatables()->of($query->orderBy('urutan'))
            ->addColumn('nama', fn($r) => $r->nama)
            ->addColumn('jenis', fn($r) => ucfirst($r->jenis))
            ->addColumn('nilai', fn($r) => $r->nilai_default ? number_format($r->nilai_default, 0, ',', '.') : '-')
            ->addColumn('penerima', fn($r) => $r->penerima_count . ' org')
            ->addColumn('status_badge', fn($r) => $r->is_active
                ? '<span class="badge bg-success-subtle text-success">Aktif</span>'
                : '<span class="badge bg-secondary-subtle">Nonaktif</span>')
            ->rawColumns(['status_badge'])
            ->make(true);
    }
}
