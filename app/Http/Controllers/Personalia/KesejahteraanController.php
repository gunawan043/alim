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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

    // -- BPJS (Asuransi) --

    public function bpjsStore(Request $request, string $userId)
    {
        $validated = $request->validate([
            'user_id'              => 'required|exists:users,id',
            'nomor_kartu'          => 'required|string|max:50',
            'jenis_bpjs'           => 'required|in:ksm,kbp',
            'tanggal_daftar'       => 'required|date',
            'tanggal_nonaktif'     => 'nullable|date',
            'iuran_per_bulan'      => 'nullable|numeric|min:0',
            'iuran_perusahaan'     => 'nullable|numeric|min:0',
            'iuran_pekerja'        => 'nullable|numeric|min:0',
            'status'               => 'nullable|in:aktif,nonaktif',
            'catatan'              => 'nullable|string',
        ]);

        $validated['status'] = $validated['status'] ?? 'aktif';
        $validated['user_id'] = $validated['user_id'];

        try {
            Bpjs::create($validated);
        } catch (\Exception $e) {
            Log::error('bpjsStore failed', ['error' => $e->getMessage(), 'payload' => $validated]);
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Gagal menyimpan BPJS.'], 500);
            }
            return redirect()->back()->with('error', 'Gagal menyimpan BPJS. Coba lagi.');
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'BPJS berhasil disimpan.'], 201);
        }

        return redirect()->route('user.kesejahteraan.asuransi', $userId)
            ->with('success', 'Data BPJS berhasil disimpan.');
    }

    public function bpjsUpdate(Request $request, string $userId, string $id)
    {
        $bpjs = Bpjs::findOrFail($id);

        $validated = $request->validate([
            'user_id'              => 'sometimes|required|exists:users,id',
            'nomor_kartu'          => 'sometimes|required|string|max:50',
            'jenis_bpjs'           => 'sometimes|required|in:ksm,kbp',
            'tanggal_daftar'       => 'sometimes|required|date',
            'tanggal_nonaktif'     => 'nullable|date',
            'iuran_per_bulan'      => 'nullable|numeric|min:0',
            'iuran_perusahaan'     => 'nullable|numeric|min:0',
            'iuran_pekerja'        => 'nullable|numeric|min:0',
            'status'               => 'nullable|in:aktif,nonaktif',
            'catatan'              => 'nullable|string',
        ]);

        $validated['status'] = $validated['status'] ?? $bpjs->status;

        try {
            $bpjs->update($validated);
        } catch (\Exception $e) {
            Log::error('bpjsUpdate failed', ['id' => $id, 'error' => $e->getMessage()]);
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Gagal memperbarui BPJS.'], 500);
            }
            return redirect()->back()->with('error', 'Gagal memperbarui BPJS. Coba lagi.');
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'BPJS berhasil diperbarui.'], 200);
        }

        return redirect()->route('user.kesejahteraan.asuransi', $userId)
            ->with('success', 'Data BPJS berhasil diperbarui.');
    }

    public function bpjsDestroy(Request $request, string $userId, string $id)
    {
        $bpjs = Bpjs::findOrFail($id);

        try {
            $bpjs->delete();
        } catch (\Exception $e) {
            Log::error('bpjsDestroy failed', ['id' => $id, 'error' => $e->getMessage()]);
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Gagal menghapus BPJS.'], 500);
            }
            return redirect()->back()->with('error', 'Gagal menghapus BPJS. Coba lagi.');
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'BPJS berhasil dihapus.'], 200);
        }

        return redirect()->route('user.kesejahteraan.asuransi', $userId)
            ->with('success', 'Data BPJS berhasil dihapus.');
    }

    // -- Benefit / Penerima --

    public function benefitStore(Request $request, string $userId)
    {
        $validated = $request->validate([
            'kesejahteraan_id'  => 'required|exists:kesejahteraans,id',
            'user_id'           => 'required|exists:users,id',
            'nilai'             => 'nullable|numeric|min:0',
            'tanggal_mulai'     => 'required|date',
            'tanggal_selesai'   => 'nullable|date|after_or_equal:tanggal_mulai',
            'status'            => 'nullable|in:disetujui,ditolak,pending',
            'catatan'           => 'nullable|string',
        ]);

        $validated['status'] = $validated['status'] ?? 'pending';

        try {
            KesejahteraanPenerima::create($validated);
        } catch (\Exception $e) {
            Log::error('benefitStore failed', ['error' => $e->getMessage(), 'payload' => $validated]);
            return view('personalia.kesejahteraan.benefit', [
                'userId' => $userId,
                'penerima' => collect(),
                'error' => 'Gagal menyimpan benefit. ' . $e->getMessage(),
            ]);
        }

        return redirect()->route('user.kesejahteraan.benefit', $userId)
            ->with('success', 'Data benefit berhasil disimpan.');
    }

    public function benefitUpdate(Request $request, string $userId, string $id)
    {
        $penerima = KesejahteraanPenerima::findOrFail($id);

        $validated = $request->validate([
            'kesejahteraan_id'  => 'sometimes|required|exists:kesejahteraans,id',
            'user_id'           => 'sometimes|required|exists:users,id',
            'nilai'             => 'nullable|numeric|min:0',
            'tanggal_mulai'     => 'sometimes|required|date',
            'tanggal_selesai'   => 'nullable|date|after_or_equal:tanggal_mulai',
            'status'            => 'nullable|in:disetujui,ditolak,pending',
            'catatan'           => 'nullable|string',
        ]);

        try {
            $penerima->update($validated);
        } catch (\Exception $e) {
            Log::error('benefitUpdate failed', ['id' => $id, 'error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Gagal memperbarui benefit. Coba lagi.');
        }

        return redirect()->route('user.kesejahteraan.benefit', $userId)
            ->with('success', 'Data benefit berhasil diperbarui.');
    }

    public function benefitDestroy(Request $request, string $userId, string $id)
    {
        $penerima = KesejahteraanPenerima::findOrFail($id);

        try {
            $penerima->delete();
        } catch (\Exception $e) {
            Log::error('benefitDestroy failed', ['id' => $id, 'error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Gagal menghapus benefit. Coba lagi.');
        }

        return redirect()->route('user.kesejahteraan.benefit', $userId)
            ->with('success', 'Data benefit berhasil dihapus.');
    }

    // -- Klaim --

    public function klaim(Request $request, string $userId)
    {
        $query = KesejahteraanKlaim::with(['user', 'kesejahteraan', 'processor'])
            ->when($request->get('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->get('q'), fn($q, $s) => $q->whereHas('user', fn($qq) => $qq->where('name', 'like', "%$s%")))
            ->orderBy('created_at', 'desc');

        $datatables = $request->boolean('datatables', false);

        if ($datatables || $request->wantsJson()) {
            return datatables()->of($query)
                ->addColumn('nomor_klaim', fn($r) => $r->nomor_klaim)
                ->addColumn('user', fn($r) => $r->user->name ?? '-')
                ->addColumn('kesejahteraan', fn($r) => $r->kesejahteraan->nama ?? '-')
                ->addColumn('nilai_diminta', fn($r) => $r->nilai_diminta ? number_format($r->nilai_diminta, 0, ',', '.') : '-')
                ->addColumn('status_badge', function ($r) {
                    $badges = [
                        'pending'  => '<span class="badge bg-warning-subtle text-warning">Pending</span>',
                        'disetujui'=> '<span class="badge bg-success-subtle text-success">Disetujui</span>',
                        'ditolak'  => '<span class="badge bg-danger-subtle text-danger">Ditolak</span>',
                        'diproses' => '<span class="badge bg-info-subtle text-info">Diproses</span>',
                    ];
                    return $badges[$r->status] ?? $r->status;
                })
                ->rawColumns(['status_badge'])
                ->make(true);
        }

        $klaim = $query->paginate(20);
        return view('personalia.kesejahteraan.klaim', compact('userId', 'klaim'));
    }

    public function klaimStore(Request $request, string $userId)
    {
        $validated = $request->validate([
            'user_id'              => 'required|exists:users,id',
            'kesejahteraan_id'     => 'required|exists:kesejahteraans,id',
            'nomor_klaim'          => 'required|string|max:50|unique:kesejahteraan_klaim,nomor_klaim',
            'nilai_diminta'        => 'required|numeric|min:0',
            'nilai_disetujui'      => 'nullable|numeric|min:0',
            'deskripsi_kejadian'   => 'nullable|string',
            'dokumen_path'         => 'nullable|string|max:255',
            'status'               => 'nullable|in:pending,disetujui,ditolak,diproses',
            'catatan_admin'        => 'nullable|string',
        ]);

        $validated['status']    = $validated['status'] ?? 'pending';
        $validated['nomor_klaim'] = $validated['nomor_klaim'] ?? 'KLAIM-' . strtoupper(Str::random(8));

        try {
            KesejahteraanKlaim::create($validated);
        } catch (\Exception $e) {
            Log::error('klaimStore failed', ['error' => $e->getMessage(), 'payload' => $validated]);
            return redirect()->back()->with('error', 'Gagal menyimpan klaim. Coba lagi.');
        }

        return redirect()->route('user.kesejahteraan.klaim', $userId)
            ->with('success', 'Klaim berhasil disimpan.');
    }

    public function klaimProses(Request $request, string $userId, string $id, string $status)
    {
        $validStatuses = ['pending', 'disetujui', 'ditolak', 'diproses'];
        if (!in_array($status, $validStatuses, true)) {
            return redirect()->back()->with('error', 'Status tidak valid.');
        }

        $klaim = KesejahteraanKlaim::findOrFail($id);

        try {
            $klaim->update([
                'status'        => $status,
                'diproses_oleh' => Auth::id(),
                'diproses_at'   => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('klaimProses failed', ['id' => $id, 'status' => $status, 'error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Gagal memproses klaim. Coba lagi.');
        }

        $messages = [
            'pending'   => 'Klaim dikembalikan ke status pending.',
            'disetujui' => 'Klaim berhasil disetujui.',
            'ditolak'   => 'Klaim berhasil ditolak.',
            'diproses'  => 'Klaim sedang diproses.',
        ];

        return redirect()->route('user.kesejahteraan.klaim', $userId)
            ->with('success', $messages[$status] ?? 'Status klaim diperbarui.');
    }
}
