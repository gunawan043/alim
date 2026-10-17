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
        $baseQuery = AbsensiGtk::query()
            ->when($request->get('gtk_id'), fn ($q, $g) => $q->where('gtk_id', $g))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->get('tanggal'), fn ($q, $t) => $q->whereDate('tanggal', $t));

        $absensis = (clone $baseQuery)->with(['gtk', 'pembuat'])
            ->orderBy('tanggal', 'desc')
            ->paginate(20);

        $gtkList = GtkProfile::orderBy('nama')->get();

        // Agregat ringan dari query terfilter (bukan query per-baris)
        $statusCounts = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'total' => (int) $statusCounts->sum(),
            'total_gtk' => (clone $baseQuery)->distinct()->count('gtk_id'),
            'hadir' => (int) ($statusCounts['hadir'] ?? 0),
            'sakit' => (int) ($statusCounts['sakit'] ?? 0),
            'izin' => (int) ($statusCounts['izin'] ?? 0),
            'alpa' => (int) ($statusCounts['alpa'] ?? 0),
            'cuti' => (int) ($statusCounts['cuti'] ?? 0),
            'dinas_luar' => (int) ($statusCounts['dinas_luar'] ?? 0),
            'terlambat' => (clone $baseQuery)->where('status', 'hadir')->where('terlambat_menit', '>', 0)->count(),
        ];
        $stats['tidak_hadir'] = $stats['sakit'] + $stats['izin'] + $stats['alpa'];

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

        $qr = app(\App\Services\QrAttendanceSettings::class);

        $qrTime = $qr->timeSettings();
        $qrGeo = $qr->geoSettings();
        $canManageQr = $this->canManageQrSettings($request);

        return view('personalia.absensi-gtk.settings', compact('userId', 'settings', 'qrTime', 'qrGeo', 'canManageQr'));
    }

    /**
     * Simpan pengaturan Toleransi Keterlambatan & Geolokasi Scan QR.
     * Hanya satuan pendidikan berwenang (TU/Struktural/Kurikulum).
     */
    public function qrSettingsStore(Request $request, string $userId)
    {
        if (! $this->canManageQrSettings($request)) {
            abort(403, 'Hanya satuan pendidikan berwenang (TU, Kepala/Wakil, Kurikulum) yang dapat mengubah pengaturan ini.');
        }

        $validated = $request->validate([
            'late_threshold_minutes' => 'required|integer|min:0|max:120',
            'tolerance_before_minutes' => 'required|integer|min:0|max:120',
            'tolerance_after_minutes' => 'required|integer|min:0|max:120',
            'early_leave_threshold_minutes' => 'required|integer|min:0|max:180',
            'geo_enabled' => 'nullable|boolean',
            'geo_lat' => 'nullable|numeric|between:-90,90|required_if:geo_enabled,1',
            'geo_lng' => 'nullable|numeric|between:-180,180|required_if:geo_enabled,1',
            'geo_radius_m' => 'nullable|integer|min:10|max:5000',
            'device_binding_enabled' => 'nullable|boolean',
        ], [
            'geo_lat.required_if' => 'Isi titik lokasi (Lintang) atau gunakan tombol "Gunakan Lokasi Saya".',
            'geo_lng.required_if' => 'Isi titik lokasi (Bujur) atau gunakan tombol "Gunakan Lokasi Saya".',
        ]);

        // Toleransi per jenis disimpan pada grup qr_settings (satu sumber dengan scan).
        $current = AbsensiGtkSetting::get(\App\Services\QrAttendanceSettings::KEY_QR_SETTINGS, []) ?: [];
        $current = is_array($current) ? $current : [];

        $oldTime = $current;
        $oldGeo = app(\App\Services\QrAttendanceSettings::class)->geoSettings();

        $current['qr_late_threshold_minutes'] = (int) $validated['late_threshold_minutes'];
        $current['qr_tolerance_before_minutes'] = (int) $validated['tolerance_before_minutes'];
        $current['qr_tolerance_after_minutes'] = (int) $validated['tolerance_after_minutes'];
        $current['qr_early_leave_threshold_minutes'] = (int) $validated['early_leave_threshold_minutes'];
        AbsensiGtkSetting::set(\App\Services\QrAttendanceSettings::KEY_QR_SETTINGS, $current, 'json', 'Pengaturan QR (toleransi & lainnya)');

        // Geolokasi opsional.
        AbsensiGtkSetting::set(\App\Services\QrAttendanceSettings::KEY_GEO_ENABLED, $request->boolean('geo_enabled'), 'bool', 'Wajib lokasi saat scan QR');
        AbsensiGtkSetting::set(\App\Services\QrAttendanceSettings::KEY_GEO_LAT, $validated['geo_lat'] ?? null, 'string', 'Lintang titik satuan');
        AbsensiGtkSetting::set(\App\Services\QrAttendanceSettings::KEY_GEO_LNG, $validated['geo_lng'] ?? null, 'string', 'Bujur titik satuan');
        AbsensiGtkSetting::set(\App\Services\QrAttendanceSettings::KEY_GEO_RADIUS, (int) ($validated['geo_radius_m'] ?? 150), 'int', 'Radius area scan (meter)');

        // Opsi B: kunci perangkat (opsional).
        AbsensiGtkSetting::set(\App\Services\TeacherDeviceBinding::KEY_ENABLED, $request->boolean('device_binding_enabled'), 'bool', 'Scan hanya dari perangkat terdaftar');

        // AUDIT LOG: catat perubahan pengaturan (siapa, kapan, nilai lama → baru).
        try {
            activity('qr-settings')
                ->causedBy($request->user())
                ->withProperties([
                    'old' => [
                        'late' => $oldTime['qr_late_threshold_minutes'] ?? null,
                        'before' => $oldTime['qr_tolerance_before_minutes'] ?? null,
                        'after' => $oldTime['qr_tolerance_after_minutes'] ?? null,
                        'early_leave' => $oldTime['qr_early_leave_threshold_minutes'] ?? null,
                        'geo_enabled' => $oldGeo['enabled'],
                        'geo_lat' => $oldGeo['lat'],
                        'geo_lng' => $oldGeo['lng'],
                        'geo_radius_m' => $oldGeo['radius_m'],
                        'device_binding' => (bool) \App\Models\AbsensiGtkSetting::get(\App\Services\TeacherDeviceBinding::KEY_ENABLED, false),
                    ],
                    'new' => [
                        'late' => $current['qr_late_threshold_minutes'],
                        'before' => $current['qr_tolerance_before_minutes'],
                        'after' => $current['qr_tolerance_after_minutes'],
                        'early_leave' => $current['qr_early_leave_threshold_minutes'],
                        'geo_enabled' => $request->boolean('geo_enabled'),
                        'geo_lat' => $validated['geo_lat'] ?? null,
                        'geo_lng' => $validated['geo_lng'] ?? null,
                        'geo_radius_m' => (int) ($validated['geo_radius_m'] ?? 150),
                        'device_binding' => $request->boolean('device_binding_enabled'),
                    ],
                ])
                ->log('qr.settings.updated');
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('user.absensi-gtk.settings', $userId)
            ->with('success', 'Pengaturan toleransi & lokasi scan QR berhasil disimpan.');
    }

    /**
     * Kewenangan mengubah pengaturan QR: TU, Kepala/Wakil, Kurikulum, atau permission khusus.
     */
    private function canManageQrSettings(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        if (canPermission('absensi-admin-tu') || canPermission('absensi-tata-usaha') || canPermission('absensi-kepala-sekolah')) {
            return true;
        }

        return \App\Support\UserJabatan::hasAny($user, [
            'kepala satuan pendidikan',
            'wakil kepala satuan pendidikan',
            'kepala tata usaha',
            'tata usaha',
            'staf tata usaha',
            'koordinator kurikulum',
        ]) || (method_exists($user, 'tugasTambahan') && in_array('Tim Kurikulum', $user->tugasTambahan->pluck('nama')->all(), true));
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
