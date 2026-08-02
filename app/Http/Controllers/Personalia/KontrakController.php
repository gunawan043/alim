<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use App\Models\GtkProfile;
use App\Models\KontrakKerja;
use App\Models\KontrakTemplate;
use App\Services\HRDNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KontrakController extends Controller
{
    public function __construct(private readonly HRDNotificationService $notif) {}

    public function index(Request $request, string $userId)
    {
        $query = KontrakKerja::with('gtk')
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->get('jenis'), fn ($q, $j) => $q->where('jenis_kontrak', $j))
            ->when($request->get('bulan'), fn ($q, $b) => $q->whereMonth('tanggal_mulai', $b));

        $statusCounts = KontrakKerja::selectRaw("
            SUM(CASE WHEN status = 'AKTIF' THEN 1 ELSE 0 END) as aktif,
            SUM(CASE WHEN status = 'MENJADI_TETAP' THEN 1 ELSE 0 END) as menjadi_tetap,
            SUM(CASE WHEN status = 'SELESAI' THEN 1 ELSE 0 END) as selesai,
            SUM(CASE WHEN status = 'DIBATALKAN' THEN 1 ELSE 0 END) as dibatalkan,
            SUM(CASE WHEN DATEDIFF(tanggal_selesai, CURDATE()) BETWEEN 0 AND 90 AND status = 'AKTIF' THEN 1 ELSE 0 END) as expiring
        ")->first();

        $kontraks = $query->orderBy('tanggal_mulai', 'desc')->paginate(20);

        return view('personalia.kontrak.index', compact('userId', 'kontraks', 'statusCounts'));
    }

    public function create(Request $request, string $userId)
    {
        $templates = KontrakTemplate::where('is_active', true)->orderBy('nama')->get();
        $gtkList = GtkProfile::with('user')->whereHas('user', fn ($q) => $q->where('is_active', true))->orderBy('users.name')->get();

        return view('personalia.kontrak.create', compact('userId', 'templates', 'gtkList'));
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'gtk_id' => 'required|uuid|exists:gtk_profiles,id',
            'jenis_kontrak' => 'required|in:PKWT,PKWTT,MITRA',
            'kontrak_template_id' => 'nullable|uuid|exists:kontrak_templates,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
            'jabatan' => 'nullable|string|max:100',
            'lokasi_kerja' => 'nullable|string|max:200',
            'gaji_pokok' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string',
        ]);

        $start = Carbon::parse($validated['tanggal_mulai']);
        $end = Carbon::parse($validated['tanggal_selesai']);
        $durasi = $start->diffInMonths($end);

        KontrakKerja::create([
            'gtk_id' => $validated['gtk_id'],
            'jenis_kontrak' => $validated['jenis_kontrak'],
            'kontrak_template_id' => $validated['kontrak_template_id'] ?? null,
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'durasi_bulan' => $durasi,
            'jabatan' => $validated['jabatan'] ?? null,
            'lokasi_kerja' => $validated['lokasi_kerja'] ?? null,
            'gaji_pokok' => $validated['gaji_pokok'] ?? null,
            'catatan' => $validated['catatan'] ?? null,
            'status' => 'AKTIF',
        ]);

        return redirect()->route('user.kontrak.index', $userId)
            ->with('success', 'Kontrak kerja berhasil dibuat.');
    }

    public function edit(Request $request, string $userId, string $id)
    {
        $kontrak = KontrakKerja::findOrFail($id);
        $templates = KontrakTemplate::where('is_active', true)->orderBy('nama')->get();
        $gtkList = GtkProfile::with('user')->whereHas('user', fn ($q) => $q->where('is_active', true))->orderBy('users.name')->get();

        return view('personalia.kontrak.edit', compact('userId', 'kontrak', 'templates', 'gtkList'));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $kontrak = KontrakKerja::findOrFail($id);
        $validated = $request->validate([
            'gtk_id' => 'required|uuid|exists:gtk_profiles,id',
            'jenis_kontrak' => 'required|in:PKWT,PKWTT,MITRA',
            'kontrak_template_id' => 'nullable|uuid|exists:kontrak_templates,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
            'jabatan' => 'nullable|string|max:100',
            'lokasi_kerja' => 'nullable|string|max:200',
            'gaji_pokok' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string',
        ]);

        $start = Carbon::parse($validated['tanggal_mulai']);
        $end = Carbon::parse($validated['tanggal_selesai']);
        $durasi = $start->diffInMonths($end);

        $kontrak->update([
            'gtk_id' => $validated['gtk_id'],
            'jenis_kontrak' => $validated['jenis_kontrak'],
            'kontrak_template_id' => $validated['kontrak_template_id'] ?? null,
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'durasi_bulan' => $durasi,
            'jabatan' => $validated['jabatan'] ?? null,
            'lokasi_kerja' => $validated['lokasi_kerja'] ?? null,
            'gaji_pokok' => $validated['gaji_pokok'] ?? null,
            'catatan' => $validated['catatan'] ?? null,
        ]);

        return redirect()->route('user.kontrak.index', $userId)
            ->with('success', 'Kontrak kerja berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $kontrak = KontrakKerja::findOrFail($id);
        $kontrak->delete();

        return redirect()->route('user.kontrak.index', $userId)
            ->with('success', 'Kontrak kerja berhasil dihapus.');
    }

    public function show(Request $request, string $userId, string $id)
    {
        $kontrak = KontrakKerja::with(['gtk', 'template', 'dibuatOleh'])->findOrFail($id);
        $riwayat = KontrakKerja::where('gtk_id', $kontrak->gtk_id)
            ->orderBy('tanggal_mulai', 'desc')->get();

        return view('personalia.kontrak.show', compact('userId', 'kontrak', 'riwayat'));
    }

    public function perpanjang(Request $request, string $userId, string $id)
    {
        $kontrak = KontrakKerja::findOrFail($id);
        $validated = $request->validate([
            'tanggal_selesai_baru' => 'required|date|after:tanggal_selesai',
            'alasan' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($kontrak, $validated) {
            $kontrak->update(['status' => 'SELESAI']);
            KontrakKerja::create([
                'gtk_id' => $kontrak->gtk_id,
                'jenis_kontrak' => $kontrak->jenis_kontrak,
                'kontrak_template_id' => $kontrak->kontrak_template_id,
                'tanggal_mulai' => $kontrak->tanggal_selesai,
                'tanggal_selesai' => $validated['tanggal_selesai_baru'],
                'durasi_bulan' => Carbon::parse($kontrak->tanggal_selesai)->diffInMonths(Carbon::parse($validated['tanggal_selesai_baru'])),
                'jabatan' => $kontrak->jabatan,
                'lokasi_kerja' => $kontrak->lokasi_kerja,
                'gaji_pokok' => $kontrak->gaji_pokok,
                'catatan' => $validated['alasan'],
                'status' => 'AKTIF',
            ]);
        });

        return redirect()->back()->with('success', 'Kontrak berhasil diperpanjang.');
    }

    public function generate(Request $request, string $userId, string $id)
    {
        $kontrak = KontrakKerja::with(['gtk', 'template'])->findOrFail($id);
        if (! $kontrak->template) {
            return redirect()->back()->with('error', 'Template kontrak belum dipilih.');
        }
        $isi = $kontrak->template->isi_template;
        $isi = str_replace(
            ['{{nama}}', '{{jabatan}}', '{{lokasi}}', '{{tanggal_mulai}}', '{{tanggal_selesai}}', '{{gaji}}', '{{durasi}}'],
            [
                $kontrak->gtk->nama ?? '-',
                $kontrak->jabatan ?? '-',
                $kontrak->lokasi_kerja ?? '-',
                $kontrak->tanggal_mulai->format('d F Y'),
                $kontrak->tanggal_selesai->format('d F Y'),
                'Rp '.number_format($kontrak->gaji_pokok ?? 0, 0, ',', '.'),
                $kontrak->durasi_bulan.' bulan',
            ],
            $isi
        );
        $kontrak->update(['dokumen_path' => $kontrak->dokumen_path ?? 'kontrak_'.$kontrak->id.'.pdf']);

        return view('personalia.kontrak.preview', compact('userId', 'kontrak', 'isi'));
    }

    public function expiring(Request $request, string $userId)
    {
        $days = (int) $request->get('days', 90);
        $kontraks = KontrakKerja::with('gtk')
            ->where('status', 'AKTIF')
            ->whereDate('tanggal_selesai', '>=', now())
            ->whereDate('tanggal_selesai', '<=', now()->addDays($days))
            ->orderBy('tanggal_selesai', 'asc')
            ->paginate(20);

        return view('personalia.kontrak.expiring', compact('userId', 'kontraks', 'days'));
    }

    public function remindExpiring(Request $request, string $userId)
    {
        $kontraks = KontrakKerja::with('gtk')
            ->where('status', 'AKTIF')
            ->whereDate('tanggal_selesai', '>=', now())
            ->whereDate('tanggal_selesai', '<=', now()->addDays(60))
            ->get();

        $count = 0;
        foreach ($kontraks as $k) {
            $this->notif->notifyKontrakExpiring($k);
            $count++;
        }

        return redirect()->back()->with('success', "Reminder dikirim ke $count GTK.");
    }

    public function template(Request $request, string $userId)
    {
        $templates = KontrakTemplate::orderBy('nama')->paginate(20);

        return view('personalia.kontrak.template', compact('userId', 'templates'));
    }

    public function templateStore(Request $request, string $userId)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'jenis' => 'required|in:PKWT,PKWTT,MITRA',
            'isi_template' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        KontrakTemplate::create([
            'nama' => $validated['nama'],
            'jenis' => $validated['jenis'],
            'isi_template' => $validated['isi_template'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('user.kontrak.template', $userId)
            ->with('success', 'Template kontrak berhasil dibuat.');
    }

    public function templateUpdate(Request $request, string $userId, string $id)
    {
        $template = KontrakTemplate::findOrFail($id);
        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'jenis' => 'required|in:PKWT,PKWTT,MITRA',
            'isi_template' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $template->update($validated);

        return redirect()->route('user.kontrak.template', $userId)
            ->with('success', 'Template kontrak berhasil diperbarui.');
    }

    public function templateDestroy(Request $request, string $userId, string $id)
    {
        KontrakTemplate::findOrFail($id)->delete();

        return redirect()->route('user.kontrak.template', $userId)
            ->with('success', 'Template kontrak berhasil dihapus.');
    }

    public function settings(Request $request, string $userId)
    {
        return view('personalia.kontrak.settings', compact('userId'));
    }

    public function datatable(Request $request, string $userId)
    {
        $query = KontrakKerja::with('gtk')
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s));

        return datatables()->of($query->orderBy('tanggal_mulai', 'desc'))
            ->addColumn('gtk', fn ($r) => $r->gtk?->nama ?? '-')
            ->addColumn('jenis', fn ($r) => $r->jenis_kontrak)
            ->addColumn('masa', fn ($r) => $r->tanggal_mulai->format('d/m/Y').' - '.$r->tanggal_selesai->format('d/m/Y'))
            ->addColumn('durasi', fn ($r) => $r->durasi_bulan.' bulan')
            ->addColumn('status_badge', fn ($r) => match ($r->status) {
                'AKTIF' => '<span class="badge bg-success-subtle text-success">Aktif</span>',
                'MENJADI_TETAP' => '<span class="badge bg-primary-subtle text-primary">Menjadi Tetap</span>',
                'SELESAI' => '<span class="badge bg-secondary-subtle text-secondary">Selesai</span>',
                'DIBATALKAN' => '<span class="badge bg-danger-subtle text-danger">Dibatalkan</span>',
                default => '<span class="badge bg-secondary-subtle">-</span>',
            })
            ->rawColumns(['status_badge'])
            ->make(true);
    }
}
