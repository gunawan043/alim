<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use App\Models\KinerjaPenilaian;
use App\Models\KinerjaPeriode;
use App\Models\KinerjaIndikator;
use App\Models\KinerjaKomponen;
use App\Models\KinerjaRewardPunishment;
use App\Models\GtkProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class KinerjaController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $query = KinerjaPenilaian::with(['user.gtkProfile', 'periode', 'penilai'])
            ->when($request->get('gtk_id'), fn($q, $g) => $q->where('user_id', $g))
            ->when($request->get('periode_id'), fn($q, $p) => $q->where('kinerja_periode_id', $p))
            ->when($request->get('kategori'), fn($q, $k) => $q->where('kategori_hasil', $k));

        $penilaians = $query->orderBy('tanggal_penilaian', 'desc')->paginate(20);

        $stats = [
            'total_penilaians' => KinerjaPenilaian::count(),
            'periode_aktif'    => KinerjaPeriode::where('status', 'aktif')->count(),
            'avg_score'        => round(KinerjaPenilaian::avg('total_skor') ?? 0, 1),
            'top_gtk'          => KinerjaPenilaian::with('user.gtkProfile')
                ->selectRaw('user_id, AVG(total_skor) as avg_skor')
                ->groupBy('user_id')
                ->orderByDesc('avg_skor')
                ->first(),
        ];

        $periodes = KinerjaPeriode::orderBy('tanggal_mulai', 'desc')->get();
        $gtkList = GtkProfile::with('user')
            ->whereHas('user', fn($q) => $q->where('is_active', true))
            ->orderBy('users.name')->get();

        return view('personalia.kinerja.index', compact('userId', 'penilaians', 'stats', 'periodes', 'gtkList'));
    }

    public function create(Request $request, string $userId)
    {
        $gtkList = GtkProfile::with('user')
            ->whereHas('user', fn($q) => $q->where('is_active', true))
            ->orderBy('users.name')->get();
        $periodes = KinerjaPeriode::where('status', 'aktif')->orderBy('tanggal_mulai', 'desc')->get();
        $komponens = KinerjaKomponen::where('is_active', true)->with('indikators')->orderBy('urutan')->get();

        return view('personalia.kinerja.create', compact('userId', 'gtkList', 'periodes', 'komponens'));
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'user_id'          => 'required|uuid|exists:users,id',
            'kinerja_periode_id' => 'required|uuid|exists:kinerja_periodes,id',
            'tanggal_penilaian'  => 'required|date',
            'penilai_id'  => 'nullable|uuid|exists:users,id',
            'nilai_detail' => 'nullable|array',
            'nilai_detail.*' => 'nullable|numeric|min:0|max:100',
            'catatan_rekonsiliasi' => 'nullable|string',
            'catatan_penilai' => 'nullable|string',
            'rekomendasi'=> 'nullable|string',
            'status_rekomendasi' => 'nullable|in:diterima,ditingkatkan,didemosi,dilanjutkan',
        ]);

        $nilaiDetail = $validated['nilai_detail'] ?? [];
        $totalSkor = count($nilaiDetail) > 0 ? array_sum($nilaiDetail) / count($nilaiDetail) : 0;

        KinerjaPenilaian::create([
            'user_id'            => $validated['user_id'],
            'kinerja_periode_id'=> $validated['kinerja_periode_id'],
            'penilai_id'        => $validated['penilai_id'] ?? Auth::id(),
            'tanggal_penilaian' => $validated['tanggal_penilaian'],
            'total_skor'        => round($totalSkor, 2),
            'nilai_huruf'       => KinerjaPenilaian::hitungNilaiHuruf($totalSkor),
            'kategori_hasil'    => KinerjaPenilaian::hitungKategori(KinerjaPenilaian::hitungNilaiHuruf($totalSkor)),
            'nilai_detail'      => $nilaiDetail,
            'catatan_penilai'   => $validated['catatan_penilai'] ?? null,
            'catatan_rekonsiliasi' => $validated['catatan_rekonsiliasi'] ?? null,
            'rekomendasi'       => $validated['rekomendasi'] ?? null,
            'status_rekomendasi'=> $validated['status_rekomendasi'] ?? null,
            'status'            => 'draft',
        ]);

        return redirect()->route('user.kinerja.index', $userId)
            ->with('success', 'Penilaian kinerja berhasil disimpan.');
    }

    public function show(Request $request, string $userId, string $id)
    {
        $penilaian = KinerjaPenilaian::with(['user.gtkProfile', 'periode', 'penilai'])
            ->findOrFail($id);

        $allPenilaians = KinerjaPenilaian::with(['periode', 'penilai'])
            ->where('user_id', $penilaian->user_id)
            ->orderBy('tanggal_penilaian', 'desc')
            ->limit(10)->get();

        return view('personalia.kinerja.show', compact('userId', 'penilaian', 'allPenilaians'));
    }

    public function edit(Request $request, string $userId, string $id)
    {
        $penilaian = KinerjaPenilaian::findOrFail($id);
        $gtkList = GtkProfile::with('user')
            ->whereHas('user', fn($q) => $q->where('is_active', true))
            ->orderBy('users.name')->get();
        $periodes = KinerjaPeriode::orderBy('tanggal_mulai', 'desc')->get();
        $komponens = KinerjaKomponen::where('is_active', true)->with('indikators')->orderBy('urutan')->get();

        return view('personalia.kinerja.edit', compact('userId', 'penilaian', 'gtkList', 'periodes', 'komponens'));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $penilaian = KinerjaPenilaian::findOrFail($id);

        $validated = $request->validate([
            'user_id'          => 'required|uuid|exists:users,id',
            'kinerja_periode_id' => 'required|uuid|exists:kinerja_periodes,id',
            'tanggal_penilaian'  => 'required|date',
            'penilai_id'  => 'nullable|uuid|exists:users,id',
            'nilai_detail' => 'nullable|array',
            'nilai_detail.*' => 'nullable|numeric|min:0|max:100',
            'catatan_penilai' => 'nullable|string',
            'catatan_rekonsiliasi' => 'nullable|string',
            'rekomendasi'=> 'nullable|string',
            'status_rekomendasi' => 'nullable|in:diterima,ditingkatkan,didemosi,dilanjutkan',
        ]);

        $nilaiDetail = $validated['nilai_detail'] ?? [];
        $totalSkor = count($nilaiDetail) > 0 ? array_sum($nilaiDetail) / count($nilaiDetail) : 0;

        $penilaian->update([
            'user_id'            => $validated['user_id'],
            'kinerja_periode_id'=> $validated['kinerja_periode_id'],
            'penilai_id'        => $validated['penilai_id'] ?? Auth::id(),
            'tanggal_penilaian' => $validated['tanggal_penilaian'],
            'total_skor'        => round($totalSkor, 2),
            'nilai_huruf'       => KinerjaPenilaian::hitungNilaiHuruf($totalSkor),
            'kategori_hasil'    => KinerjaPenilaian::hitungKategori(KinerjaPenilaian::hitungNilaiHuruf($totalSkor)),
            'nilai_detail'      => $nilaiDetail,
            'catatan_penilai'   => $validated['catatan_penilai'] ?? null,
            'catatan_rekonsiliasi' => $validated['catatan_rekonsiliasi'] ?? null,
            'rekomendasi'      => $validated['rekomendasi'] ?? null,
            'status_rekomendasi'=> $validated['status_rekomendasi'] ?? null,
        ]);

        return redirect()->route('user.kinerja.index', $userId)
            ->with('success', 'Penilaian kinerja berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $penilaian = KinerjaPenilaian::findOrFail($id);
        if ($penilaian->status === 'final') {
            return redirect()->back()->with('error', 'Penilaian yang sudah final tidak dapat dihapus.');
        }
        $penilaian->delete();
        return redirect()->route('user.kinerja.index', $userId)
            ->with('success', 'Penilaian berhasil dihapus.');
    }

    public function periode(Request $request, string $userId)
    {
        $periodes = KinerjaPeriode::withCount('penilaians')
            ->orderBy('tanggal_mulai', 'desc')->paginate(20);

        $stats = [
            'total' => KinerjaPeriode::count(),
            'draft'    => KinerjaPeriode::where('status', 'draft')->count(),
            'aktif'    => KinerjaPeriode::where('status', 'aktif')->count(),
            'selesai'  => KinerjaPeriode::where('status', 'selesai')->count(),
        ];

        return view('personalia.kinerja.periode', compact('userId', 'periodes', 'stats'));
    }

    public function periodeStore(Request $request, string $userId)
    {
        $validated = $request->validate([
            'nama'           => 'required|string|max:100',
            'tanggal_mulai'  => 'required|date',
            'tanggal_selesai'=> 'required|date|after:tanggal_mulai',
            'status'         => 'required|in:draft,aktif,selesai',
            'deskripsi'      => 'nullable|string',
        ]);

        $validated['created_by'] = Auth::id();
        KinerjaPeriode::create($validated);

        return redirect()->route('user.kinerja.periode', $userId)
            ->with('success', 'Periode berhasil dibuat.');
    }

    public function periodeUpdate(Request $request, string $userId, string $id)
    {
        $periode = KinerjaPeriode::findOrFail($id);
        $validated = $request->validate([
            'nama'           => 'required|string|max:100',
            'tanggal_mulai'  => 'required|date',
            'tanggal_selesai'=> 'required|date|after:tanggal_mulai',
            'status'         => 'required|in:draft,aktif,selesai',
            'deskripsi'      => 'nullable|string',
        ]);
        $periode->update($validated);
        return redirect()->route('user.kinerja.periode', $userId)
            ->with('success', 'Periode berhasil diperbarui.');
    }

    public function periodeDestroy(Request $request, string $userId, string $id)
    {
        $periode = KinerjaPeriode::findOrFail($id);
        if ($periode->penilaians()->exists()) {
            return redirect()->back()->with('error', 'Periode tidak dapat dihapus karena sudah memiliki penilaian.');
        }
        $periode->delete();
        return redirect()->route('user.kinerja.periode', $userId)
            ->with('success', 'Periode berhasil dihapus.');
    }

    public function indikator(Request $request, string $userId)
    {
        $komponens = KinerjaKomponen::with(['indikators' => fn($q) => $q->where('is_active', true)->orderBy('urutan')])
            ->where('is_active', true)
            ->orderBy('urutan')
            ->get();

        $stats = [
            'total_indikator' => KinerjaIndikator::where('is_active', true)->count(),
            'total_komponen'   => KinerjaKomponen::where('is_active', true)->count(),
            'total_bobot'      => KinerjaIndikator::where('is_active', true)->sum('bobot'),
        ];

        return view('personalia.kinerja.indikator', compact('userId', 'komponens', 'stats'));
    }

    public function komponenStore(Request $request, string $userId)
    {
        $validated = $request->validate([
            'nama'      => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
            'bobot'     => 'nullable|numeric|min:0|max:100',
            'warna'     => 'nullable|string|max:20',
            'urutan'    => 'nullable|integer',
        ]);

        $validated['is_active'] = true;
        KinerjaKomponen::create($validated);

        return redirect()->route('user.kinerja.indikator', $userId)
            ->with('success', 'Komponen berhasil dibuat.');
    }

    public function komponenUpdate(Request $request, string $userId, string $id)
    {
        $komponen = KinerjaKomponen::findOrFail($id);
        $validated = $request->validate([
            'nama'      => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
            'bobot'     => 'nullable|numeric|min:0|max:100',
            'warna'     => 'nullable|string|max:20',
            'urutan'    => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);
        $komponen->update($validated);
        return redirect()->route('user.kinerja.indikator', $userId)
            ->with('success', 'Komponen berhasil diperbarui.');
    }

    public function indikatorStore(Request $request, string $userId)
    {
        $validated = $request->validate([
            'kinerja_komponen_id' => 'required|uuid|exists:kinerja_komponens,id',
            'nama' => 'required|string|max:200',
            'deskripsi'          => 'nullable|string',
            'bobot'              => 'required|numeric|min:0|max:100',
            'jenis_nilai'        => 'nullable|in:angka,huruf,boolean',
            'min_skor'           => 'nullable|integer|min:0',
            'max_skor'           => 'nullable|integer|min:0',
            'urutan'             => 'nullable|integer',
        ]);

        $validated['is_active'] = true;
        KinerjaIndikator::create($validated);

        return redirect()->route('user.kinerja.indikator', $userId)
            ->with('success', 'Indikator berhasil dibuat.');
    }

    public function indikatorUpdate(Request $request, string $userId, string $id)
    {
        $indikator = KinerjaIndikator::findOrFail($id);
        $validated = $request->validate([
            'kinerja_komponen_id' => 'nullable|uuid|exists:kinerja_komponens,id',
            'nama'           => 'required|string|max:200',
            'deskripsi'      => 'nullable|string',
            'bobot'          => 'required|numeric|min:0|max:100',
            'jenis_nilai'    => 'nullable|in:angka,huruf,boolean',
            'min_skor'       => 'nullable|integer|min:0',
            'max_skor'       => 'nullable|integer|min:0',
            'urutan'         => 'nullable|integer',
            'is_active'      => 'nullable|boolean',
        ]);
        $indikator->update($validated);
        return redirect()->route('user.kinerja.indikator', $userId)
            ->with('success', 'Indikator berhasil diperbarui.');
    }

    public function indikatorDestroy(Request $request, string $userId, string $id)
    {
        KinerjaIndikator::findOrFail($id)->delete();
        return redirect()->route('user.kinerja.indikator', $userId)
            ->with('success', 'Indikator berhasil dihapus.');
    }

    public function reward(Request $request, string $userId)
    {
        $rewards = KinerjaRewardPunishment::with(['user', 'pembuat'])
            ->where('jenis', 'reward')
            ->when($request->get('kategori'), fn($q, $k) => $q->where('kategori', $k))
            ->orderBy('tanggal', 'desc')->paginate(20);

        $punishments = KinerjaRewardPunishment::with(['user', 'pembuat'])
            ->where('jenis', 'punishment')
            ->orderBy('tanggal', 'desc')->paginate(20);

        $stats = [
            'total_rewards'    => KinerjaRewardPunishment::where('jenis', 'reward')->count(),
            'total_punishments'=> KinerjaRewardPunishment::where('jenis', 'punishment')->count(),
            'gtk_terbaik'      => KinerjaRewardPunishment::where('jenis', 'reward')
                ->with('user')
                ->selectRaw('user_id, COUNT(*) as cnt')
                ->groupBy('user_id')
                ->orderByDesc('cnt')->first(),
        ];

        return view('personalia.kinerja.reward', compact('userId', 'rewards', 'punishments', 'stats'));
    }

    public function rewardStore(Request $request, string $userId)
    {
        $validated = $request->validate([
            'gtk_id'    => 'required|uuid|exists:gtk_profiles,id',
            'kategori'  => 'required|string|max:100',
            'nama'      => 'required|string|max:200',
            'deskripsi' => 'nullable|string',
            'tanggal'   => 'required|date',
            'dokumen_path' => 'nullable|string',
        ]);

        $validated['user_id'] = $validated['gtk_id'];
        $validated['jenis'] = 'reward';
        $validated['diberikan_oleh'] = Auth::id();
        unset($validated['gtk_id']);
        KinerjaRewardPunishment::create($validated);

        return redirect()->route('user.kinerja.reward', $userId)
            ->with('success', 'Reward berhasil disimpan.');
    }

    public function rewardDestroy(Request $request, string $userId, string $id)
    {
        KinerjaRewardPunishment::findOrFail($id)->delete();
        return redirect()->route('user.kinerja.reward', $userId)
            ->with('success', 'Reward berhasil dihapus.');
    }

    public function punishmentStore(Request $request, string $userId)
    {
        $validated = $request->validate([
            'gtk_id'    => 'required|uuid|exists:gtk_profiles,id',
            'kategori'  => 'required|string|max:100',
            'nama'      => 'required|string|max:200',
            'deskripsi' => 'nullable|string',
            'tanggal'   => 'required|date',
            'dokumen_path' => 'nullable|string',
        ]);

        $validated['user_id'] = $validated['gtk_id'];
        $validated['jenis'] = 'punishment';
        $validated['diberikan_oleh'] = Auth::id();
        unset($validated['gtk_id']);
        KinerjaRewardPunishment::create($validated);

        return redirect()->route('user.kinerja.reward', $userId)
            ->with('success', 'Pelanggaran berhasil dicatat.');
    }

    public function punishmentDestroy(Request $request, string $userId, string $id)
    {
        KinerjaRewardPunishment::findOrFail($id)->delete();
        return redirect()->route('user.kinerja.reward', $userId)
            ->with('success', 'Pelanggaran berhasil dihapus.');
    }

    public function laporan(Request $request, string $userId)
    {
        $periodes = KinerjaPeriode::orderBy('tanggal_mulai', 'desc')->get();

        $ranking = KinerjaPenilaian::with('gtk')
            ->selectRaw('gtk_id, AVG(skor_total) as avg_skor, COUNT(*) as total_penilaian')
            ->groupBy('gtk_id')
            ->orderByDesc('avg_skor')
            ->limit(20)->get();

        $scoreByPeriod = KinerjaPenilaian::with('periode')
            ->selectRaw('periode_id, AVG(skor_total) as avg_skor')
            ->groupBy('periode_id')
            ->orderBy('periode_id')
            ->get();

        $kategoriDist = KinerjaPenilaian::selectRaw('kategori, COUNT(*) as total')
            ->groupBy('kategori')->get()->keyBy('kategori');

        $gtkList = GtkProfile::with('user')
            ->whereHas('user', fn($q) => $q->where('is_active', true))
            ->orderBy('users.name')->get();

        return view('personalia.kinerja.laporan', compact('userId', 'periodes', 'ranking', 'scoreByPeriod', 'kategoriDist', 'gtkList'));
    }

    public function datatable(Request $request, string $userId)
    {
        $query = KinerjaPenilaian::with(['user.gtkProfile', 'periode', 'penilai'])
            ->when($request->get('gtk_id'), fn($q, $g) => $q->where('user_id', $g))
            ->when($request->get('periode_id'), fn($q, $p) => $q->where('kinerja_periode_id', $p));

        return datatables()->of($query->orderBy('tanggal_penilaian', 'desc'))
            ->addColumn('gtk', fn($r) => $r->user?->name ?? '-')
            ->addColumn('gtk_profile', fn($r) => $r->user?->gtkProfile?->nama ?? '-')
            ->addColumn('periode', fn($r) => $r->periode?->nama ?? '-')
            ->addColumn('tanggal_formatted', fn($r) => $r->tanggal_penilaian?->format('d/m/Y') ?? '-')
            ->addColumn('nilai_skor', fn($r) => $r->total_skor . ' (' . $r->nilai_huruf . ')')
            ->addColumn('kategori_badge', fn($r) => match($r->kategori_hasil) {
                'Sangat Baik' => '<span class="badge bg-success-subtle text-success">Sangat Baik</span>',
                'Baik'       => '<span class="badge bg-primary-subtle text-primary">Baik</span>',
                'Cukup'      => '<span class="badge bg-warning-subtle text-warning">Cukup</span>',
                'Kurang'    => '<span class="badge bg-danger-subtle text-danger">Kurang</span>',
                default      => '<span class="badge bg-secondary-subtle">-</span>',
            })
            ->rawColumns(['kategori_badge'])
            ->make(true);
    }
}
