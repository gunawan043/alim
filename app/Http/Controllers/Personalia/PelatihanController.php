<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use App\Models\Pelatihan;
use App\Models\PelatihanPeserta;
use App\Models\PelatihanSertifikasi;
use App\Models\GtkProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Str;

class PelatihanController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $query = Pelatihan::with(['createdBy', 'pesertas'])
            ->when($request->get('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->get('kategori'), fn($q, $k) => $q->where('kategori', $k))
            ->when($request->get('jenis'), fn($q, $j) => $q->where('jenis', $j));

        $pelatihans = $query->orderBy('tanggal_mulai', 'desc')->paginate(20);

        $stats = [
            'total_pelatihan' => Pelatihan::count(),
            'upcoming'        => Pelatihan::where('tanggal_mulai', '>', Carbon::now())->count(),
            'completed'       => Pelatihan::where('status', 'selesai')->count(),
            'total_peserta'   => PelatihanPeserta::count(),
        ];

        return view('personalia.pelatihan.index', compact('userId', 'pelatihans', 'stats'));
    }

    public function create(string $userId)
    {
        $gtkList = GtkProfile::with('user')
            ->whereHas('user', fn($q) => $q->where('is_active', true))
            ->orderBy('nama')
            ->get();

        $categories = [
            'kategori' => ['internal', 'eksternal'],
            'jenis'    => ['pelatihan', 'seminar', 'workshop', 'sertifikasi'],
            'status'   => ['draft', 'ditetapkan', 'selesai', 'dibatalkan'],
        ];

        return view('personalia.pelatihan.create', compact('userId', 'gtkList', 'categories'));
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'nama'            => 'required|string|max:200',
            'deskripsi'       => 'nullable|string',
            'kategori'        => 'required|in:internal,eksternal',
            'jenis'           => 'required|in:pelatihan,seminar,workshop,sertifikasi',
            'vendor'          => 'nullable|string|max:200',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'jam_mulai'       => 'nullable',
            'jam_selesai'     => 'nullable',
            'lokasi'          => 'nullable|string|max:200',
            'kapasitas'       => 'nullable|integer|min:1',
            'biaya'           => 'nullable|numeric|min:0',
            'status'          => 'required|in:draft,ditetapkan,selesai,dibatalkan',
            'materi'          => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,zip|max:20480',
        ]);

        $validated['created_by'] = Auth::id();

        if ($request->hasFile('materi')) {
            $file = $request->file('materi');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $validated['materi_path'] = $file->storeAs('pelatihan/materi', $filename, 'public');
        }

        Pelatihan::create($validated);

        return redirect()->route('user.pelatihan.index', $userId)
            ->with('success', 'Pelatihan berhasil disimpan.');
    }

    public function show(Request $request, string $userId, string $id)
    {
        $pelatihan = Pelatihan::with(['createdBy', 'pesertas.gtk.user'])
            ->findOrFail($id);

        $participants = $pelatihan->pesertas;
        $participantCount = $participants->count();

        return view('personalia.pelatihan.show', compact('userId', 'pelatihan', 'participants', 'participantCount'));
    }

    public function edit(Request $request, string $userId, string $id)
    {
        $pelatihan = Pelatihan::with([])->findOrFail($id);

        $gtkList = GtkProfile::with('user')
            ->whereHas('user', fn($q) => $q->where('is_active', true))
            ->orderBy('nama')
            ->get();

        $categories = [
            'kategori' => ['internal', 'eksternal'],
            'jenis'    => ['pelatihan', 'seminar', 'workshop', 'sertifikasi'],
            'status'   => ['draft', 'ditetapkan', 'selesai', 'dibatalkan'],
        ];

        return view('personalia.pelatihan.edit', compact('userId', 'pelatihan', 'gtkList', 'categories'));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $pelatihan = Pelatihan::findOrFail($id);

        $validated = $request->validate([
            'nama'            => 'required|string|max:200',
            'deskripsi'       => 'nullable|string',
            'kategori'        => 'required|in:internal,eksternal',
            'jenis'           => 'required|in:pelatihan,seminar,workshop,sertifikasi',
            'vendor'          => 'nullable|string|max:200',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'jam_mulai'       => 'nullable',
            'jam_selesai'     => 'nullable',
            'lokasi'          => 'nullable|string|max:200',
            'kapasitas'       => 'nullable|integer|min:1',
            'biaya'           => 'nullable|numeric|min:0',
            'status'          => 'required|in:draft,ditetapkan,selesai,dibatalkan',
            'materi'          => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,zip|max:20480',
        ]);

        if ($request->hasFile('materi')) {
            if ($pelatihan->materi_path) {
                Storage::disk('public')->delete($pelatihan->materi_path);
            }
            $file = $request->file('materi');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $validated['materi_path'] = $file->storeAs('pelatihan/materi', $filename, 'public');
        }

        $pelatihan->update($validated);

        return redirect()->route('user.pelatihan.show', [$userId, $id])
            ->with('success', 'Pelatihan berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $pelatihan = Pelatihan::findOrFail($id);

        if ($pelatihan->materi_path) {
            Storage::disk('public')->delete($pelatihan->materi_path);
        }

        $pelatihan->delete();

        return redirect()->route('user.pelatihan.index', $userId)
            ->with('success', 'Pelatihan berhasil dihapus.');
    }

    public function peserta(Request $request, string $userId, string $pelatihanId)
    {
        $pelatihan = Pelatihan::with([])->findOrFail($pelatihanId);

        $query = PelatihanPeserta::with(['gtk.user'])
            ->where('pelatihan_id', $pelatihanId);

        if ($request->get('status')) {
            $query->where('status', $request->get('status'));
        }

        $pesertas = $query->orderBy('created_at', 'desc')->paginate(20);

        $gtkList = GtkProfile::with('user')
            ->whereHas('user', fn($q) => $q->where('is_active', true))
            ->orderBy('nama')
            ->get();

        return view('personalia.pelatihan.peserta', compact('userId', 'pelatihan', 'pesertas', 'gtkList'));
    }

    public function pesertaDaftar(Request $request, string $userId, string $pelatihanId)
    {
        $validated = $request->validate([
            'gtk_ids' => 'required|array|min:1',
            'gtk_ids.*' => 'uuid|exists:gtk_profiles,id',
        ]);

        $pelatihan = Pelatihan::findOrFail($pelatihanId);
        $gtkIds = $validated['gtk_ids'];

        $existingIds = PelatihanPeserta::where('pelatihan_id', $pelatihanId)
            ->whereIn('gtk_id', $gtkIds)
            ->pluck('gtk_id')
            ->toArray();

        $toInsert = collect($gtkIds)->filter(fn($id) => !in_array($id, $existingIds))->map(fn($id) => [
            'pelatihan_id' => $pelatihanId,
            'gtk_id'       => $id,
            'status'       => 'daftar',
            'created_at'   => Carbon::now(),
            'updated_at'   => Carbon::now(),
        ])->toArray();

        if (!empty($toInsert)) {
            PelatihanPeserta::insert($toInsert);
        }

        $count = count($toInsert);
        $msg = $count > 0
            ? "$count peserta berhasil didaftarkan."
            : "GTK yang dipilih sudah terdaftar.";

        return redirect()->route('user.pelatihan.peserta', [$userId, $pelatihanId])
            ->with('success', $msg);
    }

    public function pesertaUpdateStatus(Request $request, string $userId, string $pesertaId, string $status)
    {
        $validStatuses = ['diterima', 'ditolak', 'hadir', 'tidak_hadir'];
        if (!in_array($status, $validStatuses)) {
            return redirect()->back()->with('error', 'Status tidak valid.');
        }

        $peserta = PelatihanPeserta::findOrFail($pesertaId);
        $peserta->status = $status;

        if ($status === 'hadir') {
            $peserta->tanggal_kehadiran = Carbon::now()->toDateString();
        }

        $peserta->save();

        return redirect()->back()->with('success', 'Status peserta berhasil diperbarui.');
    }

    public function pesertaHapus(Request $request, string $userId, string $pesertaId)
    {
        $peserta = PelatihanPeserta::findOrFail($pesertaId);
        $peserta->delete();

        return redirect()->back()->with('success', 'Peserta berhasil dihapus.');
    }

    public function sertifikasi(Request $request, string $userId)
    {
        $query = PelatihanSertifikasi::with(['gtk.user', 'createdBy'])
            ->when($request->get('search'), fn($q, $s) => $q->where('nama_sertifikat', 'like', "%{$s}%"))
            ->when($request->get('kategori'), fn($q, $k) => $q->where('kategori', $k))
            ->when($request->get('gtk_id'), fn($q, $g) => $q->where('gtk_id', $g));

        $sertifikasis = $query->orderBy('tanggal_terbit', 'desc')->paginate(20);

        $gtkList = GtkProfile::with('user')
            ->whereHas('user', fn($q) => $q->where('is_active', true))
            ->orderBy('nama')
            ->get();

        return view('personalia.pelatihan.sertifikasi', compact('userId', 'sertifikasis', 'gtkList'));
    }

    public function sertifikasiStore(Request $request, string $userId)
    {
        $validated = $request->validate([
            'gtk_id'           => 'required|uuid|exists:gtk_profiles,id',
            'nama_sertifikat'  => 'required|string|max:255',
            'nomor_sertifikat' => 'nullable|string|max:100',
            'institusi_penerbit'=> 'nullable|string|max:200',
            'tanggal_terbit'   => 'required|date',
            'tanggal_expired'  => 'nullable|date|after:tanggal_terbit',
            'kategori'         => 'nullable|string|max:100',
            'file_path'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $validated['created_by'] = Auth::id();

        if ($request->hasFile('file_path')) {
            $file = $request->file('file_path');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $validated['file_path'] = $file->storeAs('pelatihan/sertifikasi', $filename, 'public');
        }

        PelatihanSertifikasi::create($validated);

        return redirect()->route('user.pelatihan.sertifikasi', $userId)
            ->with('success', 'Sertifikasi berhasil disimpan.');
    }

    public function rekap(Request $request, string $userId)
    {
        $tahun = $request->get('tahun', Carbon::now()->year);

        $pelatihans = Pelatihan::with(['createdBy', 'pesertas'])
            ->whereYear('tanggal_mulai', $tahun)
            ->orderBy('tanggal_mulai', 'desc')
            ->get();

        $monthly = $pelatihans->groupBy(fn($p) => Carbon::parse($p->tanggal_mulai)->format('F'))
            ->map(fn($group) => [
                'count'  => $group->count(),
                'biaya'  => $group->sum('biaya'),
                'peserta'=> $group->sum(fn($p) => $p->pesertas->count()),
            ]);

        $totalBiaya = $pelatihans->sum('biaya');
        $totalPeserta = $pelatihans->sum(fn($p) => $p->pesertas->count());
        $totalSelesai = $pelatihans->where('status', 'selesai')->count();
        $completionRate = $pelatihans->count() > 0
            ? round(($totalSelesai / $pelatihans->count()) * 100, 1)
            : 0;

        $topParticipants = PelatihanPeserta::with(['gtk.user'])
            ->whereHas('pelatihan', fn($q) => $q->whereYear('tanggal_mulai', $tahun))
            ->get()
            ->groupBy('gtk_id')
            ->map(fn($group, $gtkId) => [
                'gtk'       => $group->first()->gtk,
                'total'     => $group->count(),
                'hadir'     => $group->where('status', 'hadir')->count(),
            ])
            ->sortByDesc('total')
            ->take(10)
            ->values();

        $stats = [
            'total_pelatihan' => $pelatihans->count(),
            'total_biaya'     => $totalBiaya,
            'total_peserta'   => $totalPeserta,
            'completion_rate' => $completionRate,
        ];

        return view('personalia.pelatihan.rekap', compact('userId', 'pelatihans', 'stats', 'monthly', 'topParticipants', 'tahun'));
    }

    public function datatable(Request $request, string $userId)
    {
        $query = Pelatihan::with(['createdBy', 'pesertas'])
            ->when($request->get('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->get('kategori'), fn($q, $k) => $q->where('kategori', $k))
            ->when($request->get('jenis'), fn($q, $j) => $q->where('jenis', $j));

        return datatables()->of($query)
            ->addColumn('nama', fn($r) => $r->nama)
            ->addColumn('kategori', fn($r) => ucfirst($r->kategori))
            ->addColumn('jenis', fn($r) => ucfirst($r->jenis))
            ->addColumn('tanggal', fn($r) => Carbon::parse($r->tanggal_mulai)->format('d/m/Y') . ' - ' . Carbon::parse($r->tanggal_selesai)->format('d/m/Y'))
            ->addColumn('lokasi', fn($r) => $r->lokasi ?? '-')
            ->addColumn('peserta_count', fn($r) => $r->pesertas->count())
            ->addColumn('biaya', fn($r) => $r->biaya ? 'Rp ' . number_format($r->biaya, 0, ',', '.') : '-')
            ->addColumn('status_badge', fn($r) => match ($r->status) {
                'draft'      => '<span class="badge bg-secondary">Draft</span>',
                'ditetapkan' => '<span class="badge bg-primary">Ditetapkan</span>',
                'selesai'    => '<span class="badge bg-success">Selesai</span>',
                'dibatalkan' => '<span class="badge bg-danger">Dibatalkan</span>',
                default      => '<span class="badge bg-secondary">-</span>',
            })
            ->rawColumns(['status_badge'])
            ->make(true);
    }
}