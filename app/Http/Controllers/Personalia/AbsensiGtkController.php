<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use App\Models\AbsensiGtk;
use App\Models\AbsensiGtkSetting;
use App\Models\GtkProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AbsensiGtkController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $absensis = AbsensiGtk::with(['gtk', 'pembuat'])
            ->when($request->get('gtk_id'), fn ($q, $g) => $q->where('gtk_id', $g))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->get('tanggal'), fn ($q, $t) => $q->whereDate('tanggal', $t))
            ->orderBy('tanggal', 'desc')
            ->paginate(20);

        $gtkList = GtkProfile::orderBy('nama')->get();

        $stats = [
            'total' => AbsensiGtk::count(),
            'hadir' => AbsensiGtk::where('status', 'hadir')->count(),
            'sakit' => AbsensiGtk::where('status', 'sakit')->count(),
            'izin' => AbsensiGtk::where('status', 'izin')->count(),
            'alpa' => AbsensiGtk::where('status', 'alpa')->count(),
        ];

        return view('personalia.absensi-gtk.index', compact('userId', 'absensis', 'gtkList', 'stats'));
    }

    public function harian(Request $request, string $userId)
    {
        $tanggal = $request->get('tanggal', now()->toDateString());
        $absensis = AbsensiGtk::with('gtk')
            ->whereDate('tanggal', $tanggal)
            ->orderBy('jam_masuk')
            ->get();
        $gtkList = GtkProfile::orderBy('nama')->get();

        return view('personalia.absensi-gtk.harian', compact('userId', 'absensis', 'gtkList', 'tanggal'));
    }

    public function rekapBulanan(Request $request, string $userId)
    {
        $bulan = $request->get('bulan', now()->month);
        $tahun = $request->get('tahun', now()->year);

        $rekap = AbsensiGtk::with('gtk')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal')
            ->get()
            ->groupBy('gtk_id');

        return view('personalia.absensi-gtk.rekap-bulanan', compact('userId', 'rekap', 'bulan', 'tahun'));
    }

    public function izin(Request $request, string $userId)
    {
        $izins = AbsensiGtk::with('gtk')
            ->whereIn('status', ['izin', 'sakit'])
            ->orderBy('tanggal', 'desc')
            ->paginate(20);

        return view('personalia.absensi-gtk.izin', compact('userId', 'izins'));
    }

    public function settings(Request $request, string $userId)
    {
        $settings = AbsensiGtkSetting::orderBy('key')->get();

        return view('personalia.absensi-gtk.settings', compact('userId', 'settings'));
    }

    public function settingsStore(Request $request, string $userId)
    {
        $validated = $request->validate([
            'items' => 'nullable|array',
            'items.*.key' => 'required_with:items|string|max:100',
            'items.*.value' => 'nullable|string',
            'items.*.type' => 'nullable|in:string,int,bool,json',
        ]);

        DB::beginTransaction();
        try {
            if (! empty($validated['items'])) {
                foreach ($validated['items'] as $row) {
                    if (! filled($row['key'] ?? null)) {
                        continue;
                    }
                    AbsensiGtkSetting::set(
                        $row['key'],
                        $row['value'] ?? null,
                        $row['type'] ?? 'string',
                    );
                }
            }
            DB::commit();

            return redirect()->route('user.absensi-gtk.settings', $userId)
                ->with('success', 'Pengaturan absensi GTK berhasil disimpan.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->withInput()
                ->with('error', 'Gagal menyimpan pengaturan.');
        }
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'gtk_id' => 'required|uuid|exists:gtk_profiles,id',
            'tanggal' => 'required|date',
            'status' => 'required|in:hadir,sakit,izin,alpa,cuti,dinas_luar',
            'jam_masuk' => 'nullable|date_format:H:i',
            'jam_pulang' => 'nullable|date_format:H:i',
            'terlambat_menit' => 'nullable|integer|min:0',
            'pulang_awal_menit' => 'nullable|integer|min:0',
            'keterangan' => 'nullable|string',
            'lokasi_masuk' => 'nullable|string|max:150',
        ]);

        $validated['dibuat_oleh'] = Auth::id();
        $validated['terlambat_menit'] = $validated['terlambat_menit'] ?? 0;
        $validated['pulang_awal_menit'] = $validated['pulang_awal_menit'] ?? 0;

        DB::beginTransaction();
        try {
            AbsensiGtk::updateOrCreate(
                ['gtk_id' => $validated['gtk_id'], 'tanggal' => $validated['tanggal']],
                $validated,
            );
            DB::commit();

            return redirect()->back()->with('success', 'Data absensi GTK berhasil disimpan.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->withInput()
                ->with('error', 'Gagal menyimpan data absensi.');
        }
    }

    public function update(Request $request, string $userId, string $id)
    {
        $absensi = AbsensiGtk::findOrFail($id);

        $validated = $request->validate([
            'gtk_id' => 'required|uuid|exists:gtk_profiles,id',
            'tanggal' => 'required|date',
            'status' => 'required|in:hadir,sakit,izin,alpa,cuti,dinas_luar',
            'jam_masuk' => 'nullable|date_format:H:i',
            'jam_pulang' => 'nullable|date_format:H:i',
            'terlambat_menit' => 'nullable|integer|min:0',
            'pulang_awal_menit' => 'nullable|integer|min:0',
            'keterangan' => 'nullable|string',
            'lokasi_masuk' => 'nullable|string|max:150',
        ]);

        DB::beginTransaction();
        try {
            $absensi->update($validated);
            DB::commit();

            return redirect()->back()->with('success', 'Data absensi GTK berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->withInput()
                ->with('error', 'Gagal memperbarui data absensi.');
        }
    }

    public function datatable(Request $request, string $userId)
    {
        $query = AbsensiGtk::with(['gtk', 'pembuat'])
            ->when($request->get('gtk_id'), fn ($q, $g) => $q->where('gtk_id', $g))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->get('tanggal'), fn ($q, $t) => $q->whereDate('tanggal', $t))
            ->orderBy('tanggal', 'desc');

        return datatables()->of($query)
            ->addColumn('gtk', fn ($r) => $r->gtk?->nama ?? '-')
            ->addColumn('tanggal_fmt', fn ($r) => $r->tanggal?->format('d/m/Y'))
            ->addColumn('jam', fn ($r) => trim(($r->jam_masuk ?? '-').' s/d '.($r->jam_pulang ?? '-')))
            ->addColumn('status_badge', function ($r) {
                $map = [
                    'hadir' => 'success',
                    'sakit' => 'warning',
                    'izin' => 'info',
                    'alpa' => 'danger',
                    'cuti' => 'secondary',
                    'dinas_luar' => 'primary',
                ];
                $color = $map[$r->status] ?? 'secondary';
                $label = ucwords(str_replace('_', ' ', $r->status));

                return '<span class="badge bg-'.$color.'-subtle text-'.$color.'">'.$label.'</span>';
            })
            ->rawColumns(['status_badge'])
            ->make(true);
    }
}
