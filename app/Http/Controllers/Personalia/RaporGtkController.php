<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use App\Models\AbsensiGtk;
use App\Models\GtkProfile;
use App\Models\KinerjaPenilaian;
use Illuminate\Http\Request;

class RaporGtkController extends Controller
{
    public function akademik(Request $request, string $userId)
    {
        $penilaians = KinerjaPenilaian::with('user')->latest('tanggal_penilaian')->get();
        $akademikList = $penilaians->groupBy('user_id')->map(function ($rows) {
            $row = $rows->first();
            $nilai = (float) ($row->total_skor ?? 0);
            return [
                'nama' => $row->user?->name ?? '-',
                'mapel' => 'Penilaian Kinerja',
                'nilai' => round($nilai, 2),
                'rata_rata' => round($rows->avg('total_skor') ?? 0, 2),
                'predikat' => $row->nilai_huruf ?? self::predikat($nilai),
            ];
        })->values();

        $totalGtk = $akademikList->count();
        $rataNilai = $totalGtk ? round($akademikList->avg('rata_rata'), 2) : 0;
        $nilaiTertinggi = $totalGtk ? $akademikList->max('rata_rata') : 0;
        $perluPerbaikan = $akademikList->where('predikat', 'D')->count();

        return view('personalia.rapor-gtk.akademik', compact('userId', 'akademikList', 'totalGtk', 'rataNilai', 'nilaiTertinggi', 'perluPerbaikan'));
    }

    public function disiplin(Request $request, string $userId)
    {
        $rows = AbsensiGtk::with('gtk.user')->get()->groupBy('gtk_id');
        $disiplinList = $rows->map(function ($items) {
            $total = $items->count();
            $hadir = $items->where('status', 'hadir')->count();
            $late = $items->where('status', 'hadir')->where('terlambat_menit', '>', 0)->count();
            $kehadiran = $total ? round($hadir / $total * 100) : 0;
            $ketepatan = $total ? round(($hadir - $late) / max(1, $hadir) * 100) : 0;
            $skor = round(($kehadiran + $ketepatan) / 2, 1);
            return [
                'nama' => $items->first()->gtk?->user?->name ?? $items->first()->gtk?->nama ?? '-',
                'kehadiran' => $kehadiran,
                'ketepatan' => $ketepatan,
                'skor' => $skor,
                'predikat' => self::predikat($skor),
            ];
        })->values();

        $totalGtk = $disiplinList->count();
        $rataKehadiran = $totalGtk ? round($disiplinList->avg('kehadiran')) : 0;
        $skorDisiplin = $totalGtk ? round($disiplinList->avg('skor'), 1) : 0;
        $skorKetepatan = $totalGtk ? round($disiplinList->avg('ketepatan')) : 0;

        return view('personalia.rapor-gtk.disiplin', compact('userId', 'disiplinList', 'totalGtk', 'rataKehadiran', 'skorDisiplin', 'skorKetepatan'));
    }

    public function kepribadian(Request $request, string $userId)
    {
        $rows = KinerjaPenilaian::with('user')->get()->groupBy('user_id');
        $kepribadianList = $rows->map(function ($items) {
            $item = $items->first();
            $detail = (array) ($item->nilai_detail ?? []);
            $score = $items->avg('total_skor') ?? 0;
            $sopan = round($detail['sopan_santun'] ?? $detail['kepribadian'] ?? $score, 1);
            $tanggung = round($detail['tanggung_jawab'] ?? $detail['kerja_sama'] ?? $score, 1);
            return [
                'nama' => $item->user?->name ?? '-',
                'sopan_santun' => $sopan,
                'tanggung_jawab' => $tanggung,
                'integritas' => round($score, 1),
                'skor' => round(($sopan + $tanggung + $score) / 3, 1),
                'predikat' => self::predikat($score),
            ];
        })->values();

        $totalGtk = $kepribadianList->count();
        $rataSopanSantun = $totalGtk ? round($kepribadianList->avg('sopan_santun'), 1) : 0;
        $rataTanggungJawab = $totalGtk ? round($kepribadianList->avg('tanggung_jawab'), 1) : 0;
        $skorKepribadian = $totalGtk ? round($kepribadianList->avg('skor'), 1) : 0;

        return view('personalia.rapor-gtk.kepribadian', compact('userId', 'kepribadianList', 'totalGtk', 'rataSopanSantun', 'rataTanggungJawab', 'skorKepribadian'));
    }

    public function administrasi(Request $request, string $userId)
    {
        $profiles = GtkProfile::with('user')->get();
        $administrasiList = $profiles->map(function (GtkProfile $profile) {
            $fields = [$profile->nik, $profile->no_kk, $profile->tanggal_lahir, $profile->jenis_kelamin, $profile->agama, $profile->status_perkawinan];
            $filled = collect($fields)->filter(fn ($value) => $value !== null && $value !== '')->count();
            $nilai = round($filled / count($fields) * 100, 1);
            $lengkap = $nilai >= 80;
            return [
                'nama' => $profile->user?->name ?? $profile->nama,
                'kelengkapan' => $lengkap ? 'lengkap' : 'belum_lengkap',
                'rapor' => $lengkap ? 'Tersedia' : 'Belum lengkap',
                'nilai' => $nilai,
                'predikat' => self::predikat($nilai),
            ];
        })->values();

        $totalGtk = $administrasiList->count();
        $dokumenLengkap = $administrasiList->where('kelengkapan', 'lengkap')->count();
        $dokumenBelumLengkap = $totalGtk - $dokumenLengkap;
        $nilaiAdmin = $totalGtk ? round($administrasiList->avg('nilai'), 1) : 0;

        return view('personalia.rapor-gtk.administrasi', compact('userId', 'administrasiList', 'totalGtk', 'dokumenLengkap', 'dokumenBelumLengkap', 'nilaiAdmin'));
    }

    public function tahunan(Request $request, string $userId)
    {
        $activeYear = \App\Models\AcademicYear::where('is_active', true)->first();
        $tahunAjaran = $activeYear?->start_date ? (int) $activeYear->start_date->format('Y') : (int) date('Y');
        $schoolId = $request->attributes->get('schoolContextId');
        $gtks = GtkProfile::query()->with('user')
            ->when($schoolId, fn ($q) => $q->whereHas('user.employment', fn ($e) => $e->where('school_id', $schoolId)))
            ->get()->sortBy(fn ($gtk) => mb_strtolower((string) $gtk->user?->name))->values();

        $penilaian = KinerjaPenilaian::with('user')->get()->groupBy('user_id');
        $tahunanList = $gtks->map(function (GtkProfile $gtk) use ($penilaian) {
            $rows = $penilaian->get($gtk->user_id, collect());
            $akademik = round($rows->avg('total_skor') ?? 0, 1);
            $rowsAbsensi = AbsensiGtk::where('gtk_id', $gtk->id)->get();
            $kehadiran = $rowsAbsensi->count() ? round($rowsAbsensi->where('status', 'hadir')->count() / $rowsAbsensi->count() * 100, 1) : 0;
            $rata = round(($akademik + $kehadiran) / 2, 1);
            return [
                'nama' => $gtk->user?->name ?? $gtk->nama,
                'akademik' => $akademik,
                'disiplin' => $kehadiran,
                'kepribadian' => $akademik,
                'administrasi' => $gtk->nik ? 100 : 60,
                'rata_rata' => $rata,
                'predikat' => self::predikat($rata),
            ];
        })->values();

        $totalGtk = $tahunanList->count();
        $rataKeseluruhan = $totalGtk ? round($tahunanList->avg('rata_rata'), 1) : 0;
        $perluPerbaikan = $tahunanList->where('predikat', 'D')->count();
        $sangatBaik = $tahunanList->where('predikat', 'A')->count();
        $academicYears = \App\Models\AcademicYear::orderByDesc('name')->get(['id', 'name']);

        return view('personalia.rapor-gtk.tahunan', compact('userId', 'tahunAjaran', 'gtks', 'academicYears', 'tahunanList', 'totalGtk', 'rataKeseluruhan', 'perluPerbaikan', 'sangatBaik'));
    }

    private static function predikat(float $nilai): string
    {
        return $nilai >= 90 ? 'A' : ($nilai >= 80 ? 'B' : ($nilai >= 70 ? 'C' : 'D'));
    }
}
