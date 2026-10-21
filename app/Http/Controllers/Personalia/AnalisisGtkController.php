<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use App\Models\GtkEmployment;
use App\Models\JadwalKbm;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Http\Request;

class AnalisisGtkController extends Controller
{
    public function bebanKerja(Request $request, string $userId)
    {
        $data = $this->workload($request);
        $bebanPerGuru = $data['items'];
        $totalGtk = count($bebanPerGuru);
        $totalJamMengajar = array_sum(array_column($bebanPerGuru, 'hours'));

        return view('personalia.analisis-gtk.beban-kerja', compact('userId', 'bebanPerGuru', 'totalGtk', 'totalJamMengajar'));
    }

    public function rasioIdeal(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $guruIds = JadwalKbm::where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->distinct()->pluck('teacher_id');
        $totalGuru = $guruIds->count();
        $rasioIdeal = '1:15';
        $rasioSaatIni = $totalGuru > 0 ? '1:'.round($this->studentCount($schoolId) / $totalGuru) : '1:0';
        $rasioList = StudyGroup::withCount(['studentClassHistories as jumlah_siswa' => fn ($q) => $q->where('is_active', true)])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')->get()
            ->map(function (StudyGroup $group) {
                $guru = JadwalKbm::where('study_group_id', $group->id)->where('is_active', true)->distinct()->count('teacher_id');
                $siswa = (int) $group->jumlah_siswa;
                $rasio = $guru > 0 ? round($siswa / $guru) : 0;
                $status = $rasio > 20 ? 'kelebihan' : ($rasio < 10 && $rasio > 0 ? 'kekurangan' : 'ideal');

                return [
                    'kelas_mapel' => $group->name,
                    'jumlah_siswa' => $siswa,
                    'jumlah_guru' => $guru,
                    'rasio_saat_ini' => '1:'.$rasio,
                    'rasio_ideal' => '1:15',
                    'status' => $status,
                ];
            });

        return view('personalia.analisis-gtk.rasio-ideal', compact('userId', 'totalGuru', 'rasioIdeal', 'rasioSaatIni', 'rasioList'));
    }

    public function proyeksi(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $gtkAktif = User::where('is_active', true)->whereHas('employment', fn ($q) => $q->when($schoolId, fn ($s) => $s->where('school_id', $schoolId)))->count();
        $gtkPensiun = User::where('is_active', true)->whereHas('employment')->whereHas('gtkProfile', fn ($q) => $q->whereNotNull('tanggal_lahir')->whereRaw('DATE_ADD(tanggal_lahir, INTERVAL 58 YEAR) <= DATE_ADD(CURDATE(), INTERVAL 24 MONTH)'))->count();
        $kebutuhanGuru = max(1, (int) ceil($this->studentCount($schoolId) / 15));
        $rekrutmenNeeded = max(0, $kebutuhanGuru - $gtkAktif);
        $proyeksiList = collect([
            ['kelas_mapel' => 'Kebutuhan Ideal Guru', 'gtk_saat_ini' => $gtkAktif, 'kebutuhan' => $kebutuhanGuru, 'aksioma' => $rekrutmenNeeded],
            ['kelas_mapel' => 'Proyeksi Pensiun 2 Tahun', 'gtk_saat_ini' => $gtkAktif, 'kebutuhan' => max(0, $gtkAktif - $gtkPensiun), 'aksioma' => $gtkPensiun],
        ]);

        return view('personalia.analisis-gtk.proyeksi', compact('userId', 'gtkAktif', 'gtkPensiun', 'kebutuhanGuru', 'rekrutmenNeeded', 'proyeksiList'));
    }

    public function gap(Request $request, string $userId)
    {
        $data = $this->workload($request);
        $gapList = collect($data['items'])->map(function ($item) {
            $gap = round(($item['hours'] ?? 0) - 18, 1);
            $status = $gap < 0 ? 'deficit' : ($gap > 0 ? 'surplus' : 'ideal');

            return [
                'nama_gtk' => $item['nama'],
                'standar' => 18,
                'aktual' => $item['hours'],
                'gap' => $gap,
                'teacher_count' => 1,
                'status' => $item['hours'] <= 0 ? 'uncovered' : $status,
            ];
        });
        $gapNegatif = $gapList->where('gap', '<', 0)->count();
        $sesuaiStandar = $gapList->where('gap', 0)->count();
        $diBawahStandar = $gapList->where('status', 'deficit')->count();
        $totalKompetensi = $gapList->count();

        return view('personalia.analisis-gtk.gap', compact('userId', 'gapList', 'gapNegatif', 'sesuaiStandar', 'diBawahStandar', 'totalKompetensi'));
    }

    private function workload(Request $request): array
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $schedules = JadwalKbm::with('teacher:id,name')
            ->where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->get();
        $teachers = User::where('is_active', true)->whereHas('employment')
            ->when($schoolId, fn ($q) => $q->whereHas('employment', fn ($e) => $e->where('school_id', $schoolId)))
            ->orderBy('name')->get(['id', 'name']);

        $items = $teachers->map(function (User $teacher) use ($schedules) {
            $rows = $schedules->where('teacher_id', $teacher->id);
            $hours = $rows->sum(function (JadwalKbm $row) {
                if (! $row->start_time || ! $row->end_time) {
                    return 1;
                }
                return max(0, (strtotime($row->end_time) - strtotime($row->start_time)) / 3600);
            });
            $status = $hours > 40 ? 'surplus' : ($hours >= 18 ? 'balanced' : 'underload');

            return [
                'nama' => $teacher->name,
                'teaching' => round($hours, 1),
                'additional' => 0,
                'hours' => round($hours, 1),
                'status' => $status,
                'details' => ['subject_count' => $rows->pluck('subject_id')->unique()->count()],
            ];
        })->values()->all();

        return ['items' => $items];
    }

    private function studentCount(?string $schoolId): int
    {
        return \App\Models\Student::where('status', 'active')->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count();
    }
}
