<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use App\Models\AbsensiGtk;
use App\Models\AcademicYear;
use App\Models\CutiRequest;
use App\Models\JadwalKbm;
use App\Models\TeacherClassAttendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class KehadiranController extends Controller
{
    /** Batas rentang rekap agar tidak membebani server (hari). */
    private const MAX_RANGE_DAYS = 92;

    private const STATUS_LABELS = [
        'tepat' => 'Tepat',
        'terlambat' => 'Terlambat',
        'keluar_cepat' => 'Keluar Cepat',
        'belum_keluar' => 'Belum Keluar',
        'tidak_hadir' => 'Tidak Hadir',
    ];

    /**
     * Rekap Kehadiran GTK - Pergantian Jam (jadwal vs scan QR masuk/keluar).
     */
    public function pergantianJam(Request $request, string $userId)
    {
        $rekap = $this->buildRekap($request);

        return view('personalia.kehadiran.pergantian-jam', array_merge($rekap, ['userId' => $userId]));
    }

    /**
     * Export rekap pergantian jam ke Excel.
     */
    public function pergantianJamExport(Request $request, string $userId)
    {
        $rekap = $this->buildRekap($request);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Pergantian Jam');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        $headers = [
            'Tanggal', 'Hari', 'Guru', 'Rombel', 'Mata Pelajaran',
            'Jam Jadwal', 'Scan Masuk', 'Scan Keluar',
            'Terlambat (mnt)', 'Keluar Cepat (mnt)', 'Durasi (mnt)', 'Status',
        ];

        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:L1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($rekap['rows'] as $item) {
            $jamJadwal = trim(($item['scheduled_start'] ? substr($item['scheduled_start'], 0, 5) : '').' - '.($item['scheduled_end'] ? substr($item['scheduled_end'], 0, 5) : ''), ' -');

            $sheet->setCellValue('A'.$row, $item['date']->format('Y-m-d'));
            $sheet->setCellValue('B'.$row, $item['day_name']);
            $sheet->setCellValue('C'.$row, $item['teacher']);
            $sheet->setCellValue('D'.$row, $item['study_group']);
            $sheet->setCellValue('E'.$row, $item['subject']);
            $sheet->setCellValue('F'.$row, $jamJadwal);
            $sheet->setCellValue('G'.$row, $item['scan_in']?->format('H:i') ?? '-');
            $sheet->setCellValue('H'.$row, $item['scan_out']?->format('H:i') ?? '-');
            $sheet->setCellValue('I'.$row, $item['late_minutes']);
            $sheet->setCellValue('J'.$row, $item['early_minutes']);
            $sheet->setCellValue('K'.$row, $item['duration']);
            $sheet->setCellValue('L'.$row, $item['status_label']);
            $row++;
        }

        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'rekap-pergantian-jam-'.$rekap['start'].'-'.$rekap['end'].'.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Rekap Kehadiran GTK.
     */
    public function rekap(Request $request, string $userId)
    {
        $schoolId = $this->resolveSchoolId($request);
        $month = $this->parseMonth($request->input('periode'));

        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $gtkId = $request->input('gtk_id');

        $gtkQuery = User::query()
            ->with(['employment'])
            ->whereHas('employment', fn ($e) => $e->when($schoolId, fn ($x) => $x->where('school_id', $schoolId)))
            ->when($gtkId, fn ($q) => $q->where('id', $gtkId))
            ->orderBy('name');

        $attendanceByUser = AbsensiGtk::query()
            ->with('gtk:id,user_id')
            ->whereDate('tanggal', '>=', $start->toDateString())
            ->whereDate('tanggal', '<=', $end->toDateString())
            ->when($schoolId, fn ($q) => $q->whereHas(
                'gtk.user.employment',
                fn ($e) => $e->where('school_id', $schoolId)
            ))
            ->get()
            ->filter(fn ($row) => $row->gtk?->user_id)
            ->toBase()
            ->groupBy(fn ($row) => $row->gtk->user_id);

        $userIds = (clone $gtkQuery)->pluck('id');
        $allRows = $attendanceByUser->only($userIds->all())->flatten(1);

        $totalGtk = $userIds->count();
        $totalHadir = $allRows->where('status', 'hadir')->count();
        $terlambat = $allRows->where('status', 'hadir')->where('terlambat_menit', '>', 0)->count();
        $tidakHadir = $allRows->whereIn('status', ['izin', 'sakit', 'alpa', 'alpha'])->count();

        $kehadiranList = $gtkQuery->paginate(25)->withQueryString()
            ->through(function (User $user) use ($attendanceByUser) {
                $rows = $attendanceByUser->get($user->id, collect());

                return [
                    'nama' => $user->name,
                    'jenis' => $user->employment?->jenis_gtk
                        ? ucwords(str_replace('_', ' ', $user->employment->jenis_gtk))
                        : ($user->employment?->jabatan ?: 'GTK'),
                    'hadir' => $rows->where('status', 'hadir')->count(),
                    'izin' => $rows->where('status', 'izin')->count(),
                    'sakit' => $rows->where('status', 'sakit')->count(),
                    'alfa' => $rows->whereIn('status', ['alpa', 'alpha'])->count(),
                    'terlambat' => $rows->where('status', 'hadir')->where('terlambat_menit', '>', 0)->count(),
                    'total_hari' => $rows->count(),
                ];
            });

        $gtkOptions = (clone $gtkQuery)->pluck('name', 'id');

        return view('personalia.kehadiran.rekap', compact(
            'userId', 'kehadiranList', 'totalGtk', 'totalHadir', 'tidakHadir',
            'terlambat', 'gtkOptions', 'month',
        ));
    }

    /**
     * Cuti & Izin GTK.
     */
    public function cutiIzin(Request $request, string $userId)
    {
        $schoolId = $this->resolveSchoolId($request);

        $statusMap = [
            'disetujui' => CutiRequest::STATUS_APPROVED,
            'menunggu' => CutiRequest::STATUS_PENDING,
            'ditolak' => CutiRequest::STATUS_REJECTED,
        ];

        $base = CutiRequest::query()
            ->when($schoolId, fn ($q) => $q->whereHas(
                'user.employment',
                fn ($e) => $e->where('school_id', $schoolId)
            ))
            ->when($request->filled('gtk_id'), fn ($q) => $q->where('user_id', $request->input('gtk_id')))
            ->when($request->filled('jenis'), fn ($q) => $q->whereHas(
                'template',
                fn ($t) => $t->where('jenis', $request->input('jenis'))
            ))
            ->when($request->filled('status'), fn ($q) => $q->where(
                'status',
                $statusMap[(string) $request->input('status')] ?? $request->input('status')
            ));

        $stats = [
            'total' => (clone $base)->count(),
            'disetujui' => (clone $base)->where('status', CutiRequest::STATUS_APPROVED)->count(),
            'menunggu' => (clone $base)->where('status', CutiRequest::STATUS_PENDING)->count(),
            'ditolak' => (clone $base)->where('status', CutiRequest::STATUS_REJECTED)->count(),
        ];

        $cutiIzinList = $base
            ->with(['user', 'template'])
            ->orderByDesc('tanggal_mulai')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (CutiRequest $cuti) => [
                'nama' => $cuti->user?->name ?? '-',
                'jenis' => $cuti->template?->nama ?: ucfirst(strtolower($cuti->template?->jenis ?? '-')),
                'tanggal_mulai' => $cuti->tanggal_mulai?->format('d/m/Y'),
                'tanggal_selesai' => $cuti->tanggal_selesai?->format('d/m/Y'),
                'durasi' => $cuti->jumlah_hari,
                'alasan' => $cuti->alasan,
                'status' => strtolower($cuti->status),
            ]);

        $gtkOptions = User::query()
            ->whereHas('employment', fn ($e) => $e->when($schoolId, fn ($x) => $x->where('school_id', $schoolId)))
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('personalia.kehadiran.cuti-izin', array_merge(
            compact('userId', 'cutiIzinList', 'gtkOptions'),
            $stats,
        ));
    }

    private function resolveSchoolId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId')
            ?? $request->user()?->employment?->school_id;
    }

    private function parseMonth(?string $value): Carbon
    {
        try {
            return $value
                ? Carbon::createFromFormat('Y-m', $value)->startOfMonth()
                : now()->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }

    /**
     * Bangun data rekap: jadwal seharusnya + scan aktual + status tidak hadir.
     *
     * @return array<string, mixed>
     */
    private function buildRekap(Request $request): array
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $activeAy = AcademicYear::where('is_active', true)->first();

        $end = $this->parseDate($request->input('end_date')) ?? today();
        $start = $this->parseDate($request->input('start_date')) ?? today()->startOfMonth();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy(), $start->copy()];
        }

        if ($start->diffInDays($end) > self::MAX_RANGE_DAYS) {
            $start = $end->copy()->subDays(self::MAX_RANGE_DAYS);
        }

        $teacherId = $request->input('teacher_id');
        $studyGroupId = $request->input('study_group_id');
        $subjectId = $request->input('subject_id');
        $statusFilter = $request->input('status');

        // Jadwal aktif (sumber "guru seharusnya mengajar kapan").
        $jadwals = JadwalKbm::with(['teacher:id,name', 'studyGroup:id,name,code', 'subject:id,name'])
            ->where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($activeAy, fn ($q) => $q->where('academic_year_id', $activeAy->id))
            ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
            ->when($studyGroupId, fn ($q) => $q->where('study_group_id', $studyGroupId))
            ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
            ->get()
            ->groupBy('day_of_week');

        // Absensi aktual pada periode.
        $attendances = TeacherClassAttendance::with(['teacher:id,name', 'studyGroup:id,name,code', 'jadwalKbm.subject:id,name'])
            ->whereBetween('attendance_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
            ->when($studyGroupId, fn ($q) => $q->where('study_group_id', $studyGroupId))
            ->when($subjectId, fn ($q) => $q->whereHas('jadwalKbm', fn ($j) => $j->where('subject_id', $subjectId)))
            ->get()
            ->keyBy(fn ($a) => $a->attendance_date->format('Y-m-d').'|'.$a->jadwal_kbm_id);

        $rows = [];
        $matched = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dayOfWeek = (int) $date->dayOfWeekIso;

            if ($dayOfWeek > 6) {
                continue;
            }

            foreach ($jadwals[$dayOfWeek] ?? collect() as $jadwal) {
                $key = $date->format('Y-m-d').'|'.$jadwal->id;
                $matched[$key] = true;
                $rows[] = $this->buildRow($date->copy(), $jadwal, $attendances->get($key));
            }
        }

        // Absensi yang jadwalnya sudah tidak aktif — tetap tampilkan agar tidak hilang dari rekap.
        foreach ($attendances as $key => $attendance) {
            if (isset($matched[$key])) {
                continue;
            }

            $rows[] = $this->buildRow($attendance->attendance_date->copy(), $attendance->jadwalKbm, $attendance);
        }

        usort($rows, function ($a, $b) {
            return [$a['date']->format('Y-m-d'), (string) $a['scheduled_start']]
                <=> [$b['date']->format('Y-m-d'), (string) $b['scheduled_start']];
        });

        $stats = [
            'total' => count($rows),
            'tepat' => 0,
            'terlambat' => 0,
            'keluar_cepat' => 0,
            'belum_keluar' => 0,
            'tidak_hadir' => 0,
        ];

        foreach ($rows as $row) {
            $stats[$row['status']] = ($stats[$row['status']] ?? 0) + 1;
        }

        $stats['persen_kehadiran'] = $stats['total'] > 0
            ? (int) round((($stats['total'] - $stats['tidak_hadir']) / $stats['total']) * 100)
            : 0;

        if ($statusFilter && isset(self::STATUS_LABELS[$statusFilter])) {
            $rows = array_values(array_filter($rows, fn ($row) => $row['status'] === $statusFilter));
        }

        // Opsi filter (dari seluruh jadwal aktif sekolah, bukan hasil filter).
        $optionJadwals = JadwalKbm::with(['teacher:id,name', 'studyGroup:id,name,code', 'subject:id,name'])
            ->where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($activeAy, fn ($q) => $q->where('academic_year_id', $activeAy->id))
            ->get();

        $teachers = $optionJadwals->pluck('teacher')->filter()->unique('id')->sortBy('name')->values();
        $studyGroups = $optionJadwals->pluck('studyGroup')->filter()->unique('id')->sortBy('name')->values();
        $subjects = $optionJadwals->pluck('subject')->filter()->unique('id')->sortBy('name')->values();

        return [
            'rows' => $rows,
            'stats' => $stats,
            'teachers' => $teachers,
            'studyGroups' => $studyGroups,
            'subjects' => $subjects,
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'filters' => [
                'teacher_id' => $teacherId,
                'study_group_id' => $studyGroupId,
                'subject_id' => $subjectId,
                'status' => $statusFilter,
            ],
            'statusLabels' => self::STATUS_LABELS,
            'activeAy' => $activeAy,
        ];
    }

    /**
     * Susun satu baris rekap (jadwal vs absensi aktual).
     *
     * @return array<string, mixed>
     */
    private function buildRow(Carbon $date, ?JadwalKbm $jadwal, ?TeacherClassAttendance $attendance): array
    {
        $lateMinutes = (int) ($attendance->late_minutes ?? 0);
        $earlyMinutes = (int) ($attendance->early_leave_minutes ?? 0);

        if (! $attendance) {
            $status = 'tidak_hadir';
        } elseif ($lateMinutes > 0 || $attendance->status_masuk === 'terlambat') {
            $status = 'terlambat';
        } elseif ($earlyMinutes > 0 || $attendance->status_keluar === 'keluar_cepat') {
            $status = 'keluar_cepat';
        } elseif ($attendance->actual_time_out) {
            $status = 'tepat';
        } else {
            $status = 'belum_keluar';
        }

        return [
            'date' => $date,
            'day_name' => $this->dayName((int) $date->dayOfWeekIso),
            'teacher' => $attendance?->teacher?->name ?? $jadwal?->teacher?->name ?? '-',
            'study_group' => $attendance?->studyGroup?->name ?? $jadwal?->studyGroup?->name ?? '-',
            'subject' => $attendance?->jadwalKbm?->subject?->name ?? $jadwal?->subject?->name ?? '-',
            'scheduled_start' => $attendance?->scheduled_start_time ?? $jadwal?->start_time,
            'scheduled_end' => $attendance?->scheduled_end_time ?? $jadwal?->end_time,
            'scan_in' => $attendance?->actual_time_in,
            'scan_out' => $attendance?->actual_time_out,
            'late_minutes' => $lateMinutes,
            'early_minutes' => $earlyMinutes,
            'duration' => (int) ($attendance->duration_minutes ?? 0),
            'status' => $status,
            'status_label' => self::STATUS_LABELS[$status] ?? $status,
        ];
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function dayName(int $dayOfWeekIso): string
    {
        return match ($dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
            default => '-',
        };
    }
}
