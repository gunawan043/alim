<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesReportScope;
use App\Models\AbsensiGtk;
use App\Models\AcademicYear;
use App\Models\Alumni;
use App\Models\Dormitory;
use App\Models\DormitoryResident;
use App\Models\GtkEmployment;
use App\Models\School;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\User;
use App\Support\Reports\ReportScope;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class UserLaporanController extends Controller
{
    use ResolvesReportScope;

    /**
     * Dashboard Laporan Pondok untuk Mudir, Wadir, Satuan Pendidikan.
     */
    public function index(Request $request, string $userId)
    {
        $scope = $this->reportScope($request);

        $stats = $this->buildStats($scope);

        $recentStudents = $this->applyStudentScope(Student::query(), $scope)
            ->latest()
            ->limit(5)
            ->get();

        $recentGtk = $this->applyGtkScope(User::query(), $scope)
            ->with(['gtkProfile', 'employment'])
            ->latest()
            ->limit(5)
            ->get();

        return view('user.laporan.index', compact('userId', 'stats', 'recentStudents', 'recentGtk', 'scope'));
    }

    /**
     * Laporan Presensi GTK.
     */
    public function presensi(Request $request, string $userId)
    {
        $scope = $this->reportScope($request);
        [$dari, $sampai] = $this->dateRange($request);

        $base = $this->presensiQuery($request, $scope);

        $statusCounts = (clone $base)
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $summary = [
            'total' => (int) $statusCounts->sum(),
            'hadir' => (int) ($statusCounts['hadir'] ?? 0),
            'sakit' => (int) ($statusCounts['sakit'] ?? 0),
            'izin' => (int) ($statusCounts['izin'] ?? 0),
            'alpa' => (int) ($statusCounts['alpa'] ?? 0),
            'cuti' => (int) ($statusCounts['cuti'] ?? 0),
            'dinas_luar' => (int) ($statusCounts['dinas_luar'] ?? 0),
            'terlambat' => (clone $base)->where('status', 'hadir')->where('terlambat_menit', '>', 0)->count(),
        ];

        $absensi = (clone $base)
            ->with(['gtk.user'])
            ->orderByDesc('tanggal')
            ->orderByDesc('jam_masuk')
            ->paginate(25)
            ->withQueryString();

        // Tren harian untuk grafik.
        $trend = (clone $base)
            ->select('tanggal')
            ->selectRaw("SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir")
            ->selectRaw("SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin")
            ->selectRaw("SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit")
            ->selectRaw("SUM(CASE WHEN status IN ('alpa', 'alpha') THEN 1 ELSE 0 END) as alpa")
            ->selectRaw("SUM(CASE WHEN status = 'hadir' AND terlambat_menit > 0 THEN 1 ELSE 0 END) as terlambat")
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get()
            ->map(fn ($row) => [
                'day' => Carbon::parse($row->tanggal)->format('d/m'),
                'hadir' => (int) $row->hadir,
                'izin' => (int) $row->izin,
                'sakit' => (int) $row->sakit,
                'alpa' => (int) $row->alpa,
                'terlambat' => (int) $row->terlambat,
            ])
            ->values();

        return view('user.laporan.presensi', compact(
            'userId', 'absensi', 'summary', 'dari', 'sampai', 'trend', 'scope',
        ));
    }

    /**
     * Laporan Santri (roster, status, jenis kelamin).
     */
    public function santri(Request $request, string $userId)
    {
        $scope = $this->reportScope($request);

        $base = $this->santriQuery($request, $scope);

        $summary = [
            'total' => (clone $base)->count(),
            'aktif' => (clone $base)->where('status', 'active')->count(),
            'laki' => (clone $base)->where('gender', 'L')->count(),
            'perempuan' => (clone $base)->where('gender', 'P')->count(),
        ];

        $students = (clone $base)
            ->with(['currentClassHistory.studyGroup', 'school'])
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $studyGroups = $this->applySchoolScope(StudyGroup::query(), $scope)
            ->when($this->activeYearId(), fn ($q, $ay) => $q->where('academic_year_id', $ay))
            ->orderBy('name')
            ->get();

        return view('user.laporan.santri', compact(
            'userId', 'students', 'summary', 'studyGroups', 'scope',
        ));
    }

    /**
     * Laporan GTK.
     */
    public function gtk(Request $request, string $userId)
    {
        $scope = $this->reportScope($request);

        $base = $this->gtkQuery($request, $scope);

        $summary = [
            'total' => (clone $base)->count(),
            'aktif' => (clone $base)->where('is_active', true)->count(),
            'nonaktif' => (clone $base)->where('is_active', false)->count(),
        ];

        $gtk = (clone $base)
            ->with(['gtkProfile', 'employment'])
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $jenisOptions = $this->applySchoolScope(GtkEmployment::query(), $scope)
            ->whereNotNull('jenis_gtk')
            ->distinct()
            ->orderBy('jenis_gtk')
            ->pluck('jenis_gtk');

        return view('user.laporan.gtk', compact(
            'userId', 'gtk', 'summary', 'jenisOptions', 'scope',
        ));
    }

    /**
     * Laporan Keuangan — modul keuangan pondok belum tersedia.
     */
    public function keuangan(Request $request, string $userId)
    {
        $scope = $this->reportScope($request);

        return view('user.laporan.keuangan', compact('userId', 'scope'));
    }

    /**
     * Laporan Asrama (penghuni & kapasitas).
     */
    public function asrama(Request $request, string $userId)
    {
        $scope = $this->reportScope($request);
        $q = trim((string) $request->input('q'));

        $base = Dormitory::query()
            ->when($scope->isSchool(), fn ($x) => $x->where('school_id', $scope->schoolId))
            ->when($scope->isDormitory(), fn ($x) => $x->whereIn('id', $scope->dormitoryIds))
            ->when($scope->isEmpty(), fn ($x) => $x->whereRaw('1 = 0'))
            ->when($q !== '', fn ($x) => $x->where('name', 'like', "%{$q}%"));

        $summary = [
            'total' => (clone $base)->count(),
            'kapasitas' => (int) (clone $base)->sum('capacity'),
            'penghuni' => DormitoryResident::query()
                ->where('is_active', true)
                ->when($scope->isSchool(), fn ($x) => $x->whereHas('dormitory', fn ($d) => $d->where('school_id', $scope->schoolId)))
                ->when($scope->isDormitory(), fn ($x) => $x->whereIn('dormitory_id', $scope->dormitoryIds))
                ->when($scope->isEmpty(), fn ($x) => $x->whereRaw('1 = 0'))
                ->count(),
        ];
        $summary['kosong'] = max(0, $summary['kapasitas'] - $summary['penghuni']);

        $dormitories = (clone $base)
            ->withCount([
                'rooms',
                'residents as active_residents_count' => fn ($r) => $r->where('is_active', true),
            ])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('user.laporan.asrama', compact(
            'userId', 'dormitories', 'summary', 'scope',
        ));
    }

    /**
     * Cetak PDF: presensi | santri | gtk | asrama.
     */
    public function pdf(Request $request, string $userId, string $type)
    {
        abort_unless(in_array($type, ['presensi', 'santri', 'gtk', 'asrama'], true), 404);

        $scope = $this->reportScope($request);
        $school = $scope->isSchool() ? School::find($scope->schoolId) : null;
        $data = [
            'userId' => $userId,
            'school' => $school,
            'scope' => $scope,
            'printedAt' => now(),
        ];

        if ($type === 'presensi') {
            [$dari, $sampai] = $this->dateRange($request);
            $data['dari'] = $dari;
            $data['sampai'] = $sampai;
            $data['rows'] = $this->presensiQuery($request, $scope)
                ->with(['gtk.user'])
                ->orderByDesc('tanggal')
                ->orderBy('jam_masuk')
                ->limit(2000)
                ->get();
        } elseif ($type === 'santri') {
            $data['rows'] = $this->santriQuery($request, $scope)
                ->with(['currentClassHistory.studyGroup', 'school'])
                ->orderBy('name')
                ->limit(2000)
                ->get();
        } elseif ($type === 'gtk') {
            $data['rows'] = $this->gtkQuery($request, $scope)
                ->with(['employment'])
                ->orderBy('name')
                ->limit(2000)
                ->get();
        } else {
            $q = trim((string) $request->input('q'));
            $data['rows'] = Dormitory::query()
                ->when($scope->isSchool(), fn ($x) => $x->where('school_id', $scope->schoolId))
                ->when($scope->isDormitory(), fn ($x) => $x->whereIn('id', $scope->dormitoryIds))
                ->when($scope->isEmpty(), fn ($x) => $x->whereRaw('1 = 0'))
                ->when($q !== '', fn ($x) => $x->where('name', 'like', "%{$q}%"))
                ->withCount([
                    'rooms',
                    'residents as active_residents_count' => fn ($r) => $r->where('is_active', true),
                ])
                ->orderBy('name')
                ->limit(500)
                ->get();
        }

        $pdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true]);
        $pdf->loadHtml(view('user.laporan.pdf.'.$type, $data)->render());
        $pdf->setPaper('A4', in_array($type, ['presensi', 'santri', 'gtk'], true) ? 'landscape' : 'portrait');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="laporan-'.$type.'-'.now()->format('Ymd-His').'.pdf"',
        ]);
    }

    /**
     * Export CSV: santri | gtk | presensi.
     */
    public function export(Request $request, string $userId, string $type)
    {
        $scope = $this->reportScope($request);
        $filename = 'laporan-'.$type.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($request, $scope, $type) {
            $out = fopen('php://output', 'w');
            // BOM agar Excel membaca UTF-8 dengan benar.
            fwrite($out, "\xEF\xBB\xBF");

            if ($type === 'santri') {
                fputcsv($out, ['NISN', 'NIS', 'Nama', 'JK', 'Rombel', 'Status', 'Sekolah']);
                $this->santriQuery($request, $scope)
                    ->with(['currentClassHistory.studyGroup', 'school'])
                    ->orderBy('name')
                    ->chunk(500, function ($students) use ($out) {
                        foreach ($students as $student) {
                            fputcsv($out, [
                                $student->nisn,
                                $student->nis,
                                $student->name,
                                $student->gender,
                                $student->currentClassHistory?->studyGroup?->name,
                                $student->status,
                                $student->school?->name,
                            ]);
                        }
                    });
            } elseif ($type === 'gtk') {
                fputcsv($out, ['Nama', 'Jabatan', 'Jenis GTK', 'Status Kepegawaian', 'Status Akun']);
                $this->gtkQuery($request, $scope)
                    ->with(['employment'])
                    ->orderBy('name')
                    ->chunk(500, function ($users) use ($out) {
                        foreach ($users as $user) {
                            fputcsv($out, [
                                $user->name,
                                $user->employment?->jabatan,
                                $user->employment?->jenis_gtk,
                                $user->employment?->status_kepegawaian,
                                $user->is_active ? 'Aktif' : 'Nonaktif',
                            ]);
                        }
                    });
            } else {
                fputcsv($out, ['Tanggal', 'GTK', 'Status', 'Jam Masuk', 'Jam Pulang', 'Terlambat (menit)', 'Keterangan']);
                $this->presensiQuery($request, $scope)
                    ->with(['gtk.user'])
                    ->orderByDesc('tanggal')
                    ->chunk(500, function ($rows) use ($out) {
                        foreach ($rows as $row) {
                            fputcsv($out, [
                                $row->tanggal?->format('Y-m-d'),
                                $row->gtk?->user?->name,
                                $row->status,
                                $row->jam_masuk,
                                $row->jam_pulang,
                                $row->terlambat_menit,
                                $row->keterangan,
                            ]);
                        }
                    });
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ── Query builders (dipakai halaman & export) ────────────────────

    private function santriQuery(Request $request, ReportScope $scope)
    {
        $q = trim((string) $request->input('q'));

        return $this->applyStudentScope(Student::query(), $scope)
            ->when($q !== '', fn ($x) => $x->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('nisn', 'like', "%{$q}%")
                    ->orWhere('nis', 'like', "%{$q}%");
            }))
            ->when($request->filled('status'), fn ($x) => $x->where('status', $request->input('status')))
            ->when($request->filled('gender'), fn ($x) => $x->where('gender', $request->input('gender')))
            ->when($request->filled('rombel'), fn ($x) => $x->whereHas(
                'currentClassHistory',
                fn ($c) => $c->where('study_group_id', $request->input('rombel'))
            ));
    }

    private function gtkQuery(Request $request, ReportScope $scope)
    {
        $q = trim((string) $request->input('q'));

        return $this->applyGtkScope(User::query(), $scope)
            ->when($q !== '', fn ($x) => $x->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            }))
            ->when($request->filled('status'), fn ($x) => $x->where(
                'is_active',
                $request->input('status') === 'aktif'
            ))
            ->when($request->filled('jenis'), fn ($x) => $x->whereHas(
                'employment',
                fn ($e) => $e->where('jenis_gtk', $request->input('jenis'))
            ));
    }

    private function presensiQuery(Request $request, ReportScope $scope)
    {
        [$dari, $sampai] = $this->dateRange($request);
        $q = trim((string) $request->input('q'));
        $staffIds = $this->scopeStaffIds($scope);

        return AbsensiGtk::query()
            ->whereDate('tanggal', '>=', $dari)
            ->whereDate('tanggal', '<=', $sampai)
            ->when($scope->isSchool(), fn ($x) => $x->whereHas(
                'gtk.user.employment',
                fn ($e) => $e->where('school_id', $scope->schoolId)
            ))
            ->when($scope->isDormitory(), fn ($x) => $x->whereHas(
                'gtk.user',
                fn ($u) => $u->whereIn('id', $staffIds ?? [])
            ))
            ->when($scope->isEmpty(), fn ($x) => $x->whereRaw('1 = 0'))
            ->when($request->filled('status'), fn ($x) => $x->where('status', $request->input('status')))
            ->when($q !== '', fn ($x) => $x->whereHas(
                'gtk.user',
                fn ($u) => $u->where('name', 'like', "%{$q}%")
            ));
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function buildStats(ReportScope $scope): array
    {
        $activeYearId = $this->activeYearId();
        $staffIds = $this->scopeStaffIds($scope);

        $alumniQuery = $this->applySchoolScope(Alumni::query(), $scope);

        return [
            'total_siswa' => $this->applyStudentScope(Student::query(), $scope)->where('status', 'active')->count(),
            'total_gtk' => $this->applyGtkScope(User::query(), $scope)->where('is_active', true)->count(),
            'total_alumni' => (clone $alumniQuery)->count(),
            'alumni_aktif' => (clone $alumniQuery)->whereIn('working_status', ['sedang', 'sudah'])->count(),
            'total_rombel' => $this->applySchoolScope(StudyGroup::query(), $scope)
                ->when($activeYearId, fn ($q, $ay) => $q->where('academic_year_id', $ay))
                ->count(),
            'presensi_hari_ini' => AbsensiGtk::query()
                ->whereDate('tanggal', now()->toDateString())
                ->when($scope->isSchool(), fn ($q) => $q->whereHas(
                    'gtk.user.employment',
                    fn ($e) => $e->where('school_id', $scope->schoolId)
                ))
                ->when($scope->isDormitory(), fn ($q) => $q->whereHas(
                    'gtk.user',
                    fn ($u) => $u->whereIn('id', $staffIds ?? [])
                ))
                ->when($scope->isEmpty(), fn ($q) => $q->whereRaw('1 = 0'))
                ->count(),
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function dateRange(Request $request): array
    {
        try {
            $dari = $request->filled('dari')
                ? Carbon::parse($request->input('dari'))->toDateString()
                : now()->startOfMonth()->toDateString();
        } catch (\Throwable) {
            $dari = now()->startOfMonth()->toDateString();
        }

        try {
            $sampai = $request->filled('sampai')
                ? Carbon::parse($request->input('sampai'))->toDateString()
                : now()->toDateString();
        } catch (\Throwable) {
            $sampai = now()->toDateString();
        }

        if ($dari > $sampai) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        return [$dari, $sampai];
    }

    private function activeYearId(): ?string
    {
        return AcademicYear::where('is_active', true)->value('id');
    }
}
