<?php

namespace App\Services\Ai;

use App\Models\AcademicYear;
use App\Models\BankSoal;
use App\Models\GradeLevel;
use App\Models\GtkEmployment;
use App\Models\JadwalKbm;
use App\Models\RaportRegistration;
use App\Models\Soal;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Akses data read-only untuk AI Chat.
 *
 * Prinsip:
 * - agregat untuk semua pegawai;
 * - identitas/riwayat individu hanya jika pengguna punya izin
 *   student_view / gtk_view / gtk_detail_view;
 * - NIK/KK/NPWP, rekening, kesehatan, gaji, dan kredensial tidak pernah
 *   dikembalikan;
 * - selalu dibatasi school context pengguna (kecuali global view).
 */
class AiDataToolService
{
    private ?string $schoolId = null;

    private bool $global = false;

    private bool $canViewStudents = false;

    private bool $canViewGtk = false;

    private bool $canViewGtkDetail = false;

    private bool $isBroad = false;

    private ?string $userId = null;

    /** @var array<int, string> */
    private array $myStudyGroupIds = [];

    /** @var array<string, mixed> */
    private array $myTeaching = [];

    /**
     * Definisi tool gaya OpenAI Chat Completions.
     *
     * @param  array{school_id?: ?string, global?: bool, can_view_students?: bool, can_view_gtk?: bool, can_view_gtk_detail?: bool}  $context
     * @return array<int, array<string, mixed>>
     */
    public function definitions(array $context = []): array
    {
        $this->applyContext($context);

        $tools = [
            $this->tool('count_students', 'Hitung jumlah santri (agregat, tanpa identitas) berdasarkan gender, status, tingkat, rombel, atau tahun ajaran.', [
                'gender' => ['type' => 'string', 'enum' => ['L', 'P'], 'description' => 'L = laki-laki, P = perempuan'],
                'status' => ['type' => 'string', 'description' => 'Status santri: active, inactive, graduate, dropped, transfer_in, transfer_out'],
                'grade_level' => ['type' => 'string', 'description' => 'Nama atau angka tingkat, mis. "Kelas 7" atau "7"'],
                'rombel' => ['type' => 'string', 'description' => 'Nama/kode rombel, mis. "7A"'],
                'academic_year' => ['type' => 'string', 'description' => 'Nama tahun ajaran, mis. "2026/2027"'],
            ]),
            $this->tool('count_gtk', 'Hitung jumlah GTK (guru/tenaga kependidikan) berdasarkan jenis, jabatan, atau tipe posisi.', [
                'jenis_gtk' => ['type' => 'string'],
                'jabatan' => ['type' => 'string'],
                'position_type' => ['type' => 'string', 'description' => 'guru_mapel, guru_kelas, guru_bk, kepala_sekolah, wakasek, tendik'],
                'active_only' => ['type' => 'boolean', 'default' => true],
            ]),
            $this->tool('count_rombel', 'Hitung jumlah rombel berdasarkan tingkat/tahun ajaran.', [
                'grade_level' => ['type' => 'string'],
                'academic_year' => ['type' => 'string'],
                'active_only' => ['type' => 'boolean', 'default' => true],
                'include_names' => ['type' => 'boolean', 'default' => false],
            ]),
            $this->tool('attendance_summary', 'Ringkasan presensi santri (hadir/izin/sakit/alpa) dalam rentang hari terakhir atau tanggal tertentu.', [
                'days' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 30, 'default' => 7],
                'date' => ['type' => 'string', 'description' => 'Tanggal YYYY-MM-DD; jika diisi, days diabaikan'],
                'grade_level' => ['type' => 'string'],
                'rombel' => ['type' => 'string'],
            ]),
            $this->tool('attendance_trend', 'Tren presensi harian untuk grafik: jumlah hadir/izin/sakit/alpa per tanggal dalam rentang hari terakhir.', [
                'days' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 30, 'default' => 7],
                'grade_level' => ['type' => 'string'],
                'rombel' => ['type' => 'string'],
            ]),
            $this->tool('my_schedule_today', 'Ambil jadwal mengajar pengguna hari ini (atau tanggal tertentu): jam, mapel, kelas, dan ruang.', [
                'date' => ['type' => 'string', 'description' => 'Tanggal YYYY-MM-DD; default hari ini'],
            ]),
            $this->tool('class_schedule', 'Ambil jadwal pelajaran suatu rombel/kelas pada hari atau tanggal tertentu.', [
                'rombel' => ['type' => 'string', 'description' => 'Nama/kode rombel, mis. 7A'],
                'grade_level' => ['type' => 'string', 'description' => 'Nama atau angka tingkat, mis. "Kelas 7" atau "7"'],
                'date' => ['type' => 'string', 'description' => 'Tanggal YYYY-MM-DD'],
                'day' => ['type' => 'string', 'description' => 'Nama hari, mis. Senin'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 30, 'default' => 20],
            ]),
            $this->tool('school_schedule_today', 'Ambil jadwal seluruh kelas/sekolah hari ini. Gunakan jika pengguna tidak punya jadwal pribadi atau menanyakan jadwal sekolah.', [
                'grade_level' => ['type' => 'string', 'description' => 'Filter tingkat, mis. "Kelas 7" atau "7"'],
                'date' => ['type' => 'string', 'description' => 'Tanggal YYYY-MM-DD; default hari ini'],
                'day' => ['type' => 'string', 'description' => 'Nama hari, mis. Senin'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 30, 'default' => 20],
            ]),
            $this->tool('grades_summary', 'Ringkasan nilai rapor per mata pelajaran/rombel: rata-rata, jumlah nilai, dan rincian santri jika pengguna berwenang.', [
                'grade_level' => ['type' => 'string'],
                'rombel' => ['type' => 'string'],
                'subject' => ['type' => 'string'],
                'semester' => ['type' => 'string', 'enum' => ['ganjil', 'genap']],
                'academic_year' => ['type' => 'string'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 15],
            ]),
            $this->tool('tahfidz_summary', 'Ringkasan data tahfidz: jumlah kelompok, anggota, target, setoran, dan evaluasi.', []),
            $this->tool('ljk_summary', 'Ringkasan data LJK: jumlah ujian, peserta, lembar, scan, dan sinkronisasi nilai.', []),
            $this->tool('academic_overview', 'Ringkasan agregat akademik: santri, GTK, rombel, mapel, bank soal, rapor, dan tahfidz.', []),
            $this->tool('list_master_data', 'Daftar nama master data non-sensitif: subjects, grade_levels, rombel, academic_years.', [
                'type' => ['type' => 'string', 'enum' => ['subjects', 'grade_levels', 'rombel', 'academic_years']],
            ]),
        ];

        if ($this->canViewStudents) {
            $tools[] = $this->tool('search_students', 'Cari santri dan tampilkan identitas dasar (nama, NIS/NISN, gender, status, sekolah, rombel, alamat). Gunakan hanya untuk tugas yang sah.', [
                'query' => ['type' => 'string', 'description' => 'Nama, NIS, atau NISN'],
                'status' => ['type' => 'string', 'description' => 'active, inactive, graduate, dropped, transfer_in, transfer_out'],
                'gender' => ['type' => 'string', 'enum' => ['L', 'P']],
                'grade_level' => ['type' => 'string'],
                'rombel' => ['type' => 'string'],
                'academic_year' => ['type' => 'string'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20, 'default' => 10],
            ]);
            $tools[] = $this->tool('student_detail', 'Ambil identitas lengkap dan riwayat individu santri: alamat, kontak, riwayat rombel, presensi 30 hari, dan rapor terakhir. NIK/KK/rekening/kesehatan tidak tersedia.', [
                'student_id' => ['type' => 'string'],
                'nis' => ['type' => 'string'],
                'nisn' => ['type' => 'string'],
                'name' => ['type' => 'string'],
            ]);
        }

        if ($this->canViewGtk) {
            $tools[] = $this->tool('search_gtk', 'Cari GTK (guru/tenaga kependidikan) dan tampilkan identitas dasar: nama, email, NUPY, jenis GTK, jabatan, posisi, sekolah, status kepegawaian.', [
                'query' => ['type' => 'string', 'description' => 'Nama, email, atau NUPY'],
                'jenis_gtk' => ['type' => 'string'],
                'jabatan' => ['type' => 'string'],
                'position_type' => ['type' => 'string'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20, 'default' => 10],
            ]);
        }

        if ($this->canViewGtkDetail) {
            $tools[] = $this->tool('gtk_detail', 'Ambil detail GTK: kontak, alamat, dan riwayat penugasan. NIK/KK/NPWP, gaji, dan data kesehatan tidak tersedia.', [
                'user_id' => ['type' => 'string'],
                'nupy' => ['type' => 'string'],
                'name' => ['type' => 'string'],
            ]);
        }

        if ($this->myStudyGroupIds !== []) {
            $tools[] = $this->tool('count_my_students', 'Hitung jumlah santri aktif di kelas/rombel yang diampu pengguna saat ini, beserta rincian per rombel.', []);
            $tools[] = $this->tool('list_my_classes', 'Daftar kelas/rombel dan mata pelajaran yang diampu pengguna saat ini.', []);
        }

        return $tools;
    }

    /**
     * @param  array<string, mixed>  $args
     * @param  array{school_id?: ?string, global?: bool}  $context
     * @return array<string, mixed>
     */
    public function execute(string $name, array $args, array $context): array
    {
        $this->applyContext($context);

        if (in_array($name, ['class_schedule', 'school_schedule_today'], true)) {
            $args['limit'] = min(30, max(1, (int) ($args['limit'] ?? 20)));
        }

        try {
            return match ($name) {
                'count_students' => $this->countStudents($args),
                'count_gtk' => $this->countGtk($args),
                'count_rombel' => $this->countRombel($args),
                'attendance_summary' => $this->attendanceSummary($args),
                'attendance_trend' => $this->attendanceTrend($args),
                'my_schedule_today' => $this->mySchedule($args),
                'class_schedule' => $this->classSchedule($args),
                'school_schedule_today' => $this->classSchedule($args),
                'grades_summary' => $this->gradesSummary($args),
                'tahfidz_summary' => $this->tahfidzCounts(),
                'ljk_summary' => $this->ljkSummary(),
                'academic_overview' => $this->academicOverview(),
                'list_master_data' => $this->listMasterData($args),
                'search_students' => $this->searchStudents($args),
                'student_detail' => $this->studentDetail($args),
                'search_gtk' => $this->searchGtk($args),
                'gtk_detail' => $this->gtkDetail($args),
                'count_my_students' => $this->countMyStudents(),
                'list_my_classes' => $this->listMyClasses(),
                default => ['error' => 'Tool tidak dikenal.'],
            };
        } catch (\Throwable $e) {
            report($e);

            return ['error' => 'Data tidak dapat diambil saat ini.'];
        }
    }

    /**
     * Ringkasan singkat untuk system prompt (selalu tersedia meski model
     * tidak mendukung tool calling).
     *
     * @param  array{school_id?: ?string, global?: bool}  $context
     * @return array<string, mixed>
     */
    public function snapshot(array $context): array
    {
        $this->applyContext($context);

        return [
            'scope' => $this->schoolId ? 'school' : 'global',
            'students' => [
                'total' => $this->studentQuery()->count(),
                'active' => $this->studentQuery()->where('status', 'active')->count(),
                'male_active' => $this->studentQuery()->where('status', 'active')->where('gender', 'L')->count(),
                'female_active' => $this->studentQuery()->where('status', 'active')->where('gender', 'P')->count(),
            ],
            'gtk_active' => $this->gtkQuery()->whereNotIn('status_kepegawaian', ['Berhenti', 'Wafat', 'Pensiun'])->distinct('user_id')->count('user_id'),
            'rombel_active' => $this->studyGroupQuery()->where('is_active', true)->count(),
            'subjects_active' => $this->subjectQuery()->where('is_active', true)->count(),
            'academic_years' => AcademicYear::query()->orderByDesc('start_date')->limit(5)->get(['name', 'semester', 'is_active'])->toArray(),
            'attendance_7_days' => $this->attendanceSummary(['days' => 7]),
            'raport' => $this->raportSummary(),
            'tahfidz' => $this->tahfidzCounts(),
            'ljk' => $this->ljkSummary(),
            'my_schedule_today' => $this->mySchedule([]),
            'school_schedule_today' => $this->schoolScheduleSummary(),
            'my_students' => $this->countMyStudents(),
            'my_teaching' => $this->myTeaching,
            'note' => 'Snapshot ini hanya agregat. Data pribadi tersedia lewat tool jika pengguna berwenang.',
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function applyContext(array $context): void
    {
        $this->schoolId = $context['school_id'] ?? null;
        $this->global = (bool) ($context['global'] ?? false);
        $this->canViewStudents = (bool) ($context['can_view_students'] ?? false);
        $this->canViewGtk = (bool) ($context['can_view_gtk'] ?? false);
        $this->canViewGtkDetail = (bool) ($context['can_view_gtk_detail'] ?? false);
        $this->isBroad = (bool) ($context['is_broad'] ?? false);
        $this->userId = isset($context['user_id']) ? (string) $context['user_id'] : null;
        $this->myStudyGroupIds = array_values(array_filter(array_map('strval', (array) ($context['my_study_group_ids'] ?? []))));
        $this->myTeaching = is_array($context['my_teaching'] ?? null) ? $context['my_teaching'] : [];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function countStudents(array $args): array
    {
        $query = $this->studentQuery();

        if (! empty($args['gender']) && in_array($args['gender'], ['L', 'P'], true)) {
            $query->where('gender', $args['gender']);
        }
        if (! empty($args['status'])) {
            $query->where('status', $args['status']);
        }

        $groupIds = $this->resolveStudyGroupIds($args);
        if ($groupIds !== null) {
            if ($groupIds === []) {
                return ['count' => 0, 'filters' => $args, 'note' => 'Tidak ada rombel yang cocok dengan filter.'];
            }

            $query->whereHas('classHistories', fn (Builder $q) => $q->whereIn('study_group_id', $groupIds)->where('is_active', true));
        }

        $byGender = (clone $query)->select('gender', DB::raw('COUNT(*) as total'))->groupBy('gender')->pluck('total', 'gender')->all();

        return [
            'count' => (clone $query)->count(),
            'by_gender' => ['L' => (int) ($byGender['L'] ?? 0), 'P' => (int) ($byGender['P'] ?? 0)],
            'filters' => array_filter($args, fn ($value) => filled($value)),
            'note' => 'Angka agregat; nama/NIS tidak disertakan.',
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function countGtk(array $args): array
    {
        $query = $this->gtkQuery();

        foreach (['jenis_gtk', 'jabatan', 'position_type'] as $field) {
            if (! empty($args[$field])) {
                $query->where($field, 'like', '%'.$args[$field].'%');
            }
        }

        if (filter_var($args['active_only'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
            $query->whereNotIn('status_kepegawaian', ['Berhenti', 'Wafat', 'Pensiun']);
        }

        $byPosition = (clone $query)->select('position_type', DB::raw('COUNT(DISTINCT user_id) as total'))
            ->groupBy('position_type')->pluck('total', 'position_type')->all();

        return [
            'count' => (clone $query)->distinct('user_id')->count('user_id'),
            'by_position_type' => $byPosition,
            'filters' => array_filter($args, fn ($value) => filled($value)),
            'note' => 'Agregat GTK; data pribadi/gaji tidak disertakan.',
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function countRombel(array $args): array
    {
        $query = $this->studyGroupQuery();

        if (filter_var($args['active_only'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
            $query->where('is_active', true);
        }
        if (! empty($args['academic_year'])) {
            $query->whereHas('academicYear', fn (Builder $q) => $q->where('name', 'like', '%'.$args['academic_year'].'%'));
        }
        if (! empty($args['grade_level'])) {
            $query->whereHas('gradeLevel', fn (Builder $q) => $this->filterGradeLevel($q, $args['grade_level']));
        }

        $result = [
            'count' => (clone $query)->count(),
            'filters' => array_filter($args, fn ($value) => filled($value)),
        ];

        if (filter_var($args['include_names'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $result['names'] = (clone $query)->orderBy('name')->limit(50)->pluck('name')->all();
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function attendanceSummary(array $args): array
    {
        $query = StudentAttendance::query()->when($this->schoolId && ! $this->global, fn (Builder $q) => $q->where('school_id', $this->schoolId));

        if (! empty($args['date'])) {
            $query->whereDate('attendance_date', $args['date']);
            $range = $args['date'];
        } else {
            $days = max(1, min(30, (int) ($args['days'] ?? 7)));
            $query->whereBetween('attendance_date', [now()->subDays($days - 1)->toDateString(), now()->toDateString()]);
            $range = $days.' hari terakhir';
        }

        $groupIds = $this->resolveStudyGroupIds($args);
        if ($groupIds !== null) {
            if ($groupIds === []) {
                return ['total' => 0, 'range' => $range, 'note' => 'Tidak ada rombel yang cocok.'];
            }
            $query->whereIn('study_group_id', $groupIds);
        } else {
            $this->applyDefaultGroupScope($query);
        }

        $byStatus = (clone $query)->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status')->all();
        $total = array_sum($byStatus);

        return [
            'range' => $range,
            'total' => $total,
            'by_status' => [
                'hadir' => (int) ($byStatus['hadir'] ?? 0),
                'izin' => (int) ($byStatus['izin'] ?? 0),
                'sakit' => (int) ($byStatus['sakit'] ?? 0),
                'alpa' => (int) ($byStatus['alpa'] ?? 0),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function attendanceTrend(array $args): array
    {
        $days = max(1, min(30, (int) ($args['days'] ?? 7)));
        $start = now()->subDays($days - 1)->toDateString();
        $end = now()->toDateString();

        $query = StudentAttendance::query()
            ->whereBetween('attendance_date', [$start, $end])
            ->when($this->schoolId && ! $this->global, fn (Builder $q) => $q->where('school_id', $this->schoolId));

        $groupIds = $this->resolveStudyGroupIds($args);
        if ($groupIds !== null) {
            if ($groupIds === []) {
                return ['labels' => [], 'series' => [], 'note' => 'Tidak ada rombel yang cocok.'];
            }
            $query->whereIn('study_group_id', $groupIds);
        } else {
            $this->applyDefaultGroupScope($query);
        }

        $rows = $query
            ->selectRaw('DATE(attendance_date) as tanggal, status, COUNT(*) as total')
            ->groupBy('tanggal', 'status')
            ->orderBy('tanggal')
            ->get();

        $dates = collect($rows)->pluck('tanggal')->unique()->sort()->values();
        $labels = $dates->map(fn ($date) => Carbon::parse($date)->translatedFormat('d M'))->all();
        $statuses = ['hadir', 'izin', 'sakit', 'alpa'];

        $series = [];
        foreach ($statuses as $status) {
            $byDate = collect($rows)->where('status', $status)->pluck('total', 'tanggal');
            $series[] = [
                'name' => ucfirst($status),
                'data' => $dates->map(fn ($date) => (int) ($byDate[$date] ?? 0))->all(),
            ];
        }

        return [
            'range' => $days.' hari terakhir',
            'labels' => $labels,
            'series' => $series,
            'total' => (int) collect($rows)->sum('total'),
            'note' => 'Agregat presensi harian per status.',
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function mySchedule(array $args): array
    {
        if (! $this->userId) {
            return ['error' => 'Pengguna tidak dikenali.'];
        }

        $date = $this->resolveScheduleDate($args['date'] ?? null);
        $day = $date->dayOfWeekIso;

        $query = JadwalKbm::query()
            ->with(['studyGroup:id,name,grade_level_id', 'studyGroup.gradeLevel:id,name', 'subject:id,name'])
            ->where('teacher_id', $this->userId)
            ->where('day_of_week', $day)
            ->where('is_active', true)
            ->when($this->schoolId && ! $this->global, fn (Builder $q) => $q->where('school_id', $this->schoolId))
            ->orderBy('start_time');

        $activeYearId = AcademicYear::query()->where('is_active', true)->value('id');
        if ($activeYearId) {
            $query->where('academic_year_id', $activeYearId);
        }

        $items = $query->limit(30)->get()->map(fn (JadwalKbm $schedule) => $this->scheduleRow($schedule, false))->all();

        return [
            'date' => $date->toDateString(),
            'day' => $date->copy()->locale('id')->translatedFormat('l'),
            'count' => count($items),
            'items' => $items,
            'note' => $items === []
                ? 'Tidak ada jadwal mengajar pada tanggal ini. Jika pengguna yakin ada jadwal, sarankan cek menu Jadwal KBM.'
                : 'Jadwal mengajar pengguna pada tanggal tersebut.',
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function classSchedule(array $args): array
    {
        $date = $this->resolveScheduleDate($args['date'] ?? null, $args['day'] ?? null);
        $day = $date->dayOfWeekIso;

        $query = JadwalKbm::query()
            ->with(['teacher:id,name', 'studyGroup:id,name,grade_level_id', 'studyGroup.gradeLevel:id,name', 'subject:id,name'])
            ->where('day_of_week', $day)
            ->where('is_active', true)
            ->when($this->schoolId && ! $this->global, fn (Builder $q) => $q->where('school_id', $this->schoolId))
            // Jadwal kelas bukan data pribadi: pegawai boleh melihat jadwal
            // kelas lain di sekolahnya. Tanpa konteks sekolah, batasi ke
            // kelas yang diampu agar tidak lintas sekolah.
            ->when(! $this->isBroad && ! $this->schoolId, function (Builder $q) {
                $q->whereIn('study_group_id', $this->myStudyGroupIds);
            })
            ->when(! empty($args['rombel']), function (Builder $q) use ($args) {
                $q->whereHas('studyGroup', function (Builder $group) use ($args) {
                    $group->where('name', 'like', '%'.$args['rombel'].'%')
                        ->orWhere('code', 'like', '%'.$args['rombel'].'%');
                });
            })
            ->when(! empty($args['grade_level']), function (Builder $q) use ($args) {
                $q->whereHas('studyGroup.gradeLevel', function (Builder $grade) use ($args) {
                    $grade->where('name', 'like', '%'.$args['grade_level'].'%')
                        ->orWhere('level', (int) $args['grade_level']);
                });
            })
            ->orderBy('start_time');

        $activeYearId = AcademicYear::query()->where('is_active', true)->value('id');
        if ($activeYearId) {
            $query->where('academic_year_id', $activeYearId);
        }

        $limit = max(1, min(500, (int) ($args['limit'] ?? 20)));
        $items = $query->limit($limit)->get()->map(fn (JadwalKbm $schedule) => $this->scheduleRow($schedule, true))->all();

        return [
            'date' => $date->toDateString(),
            'day' => $date->copy()->locale('id')->translatedFormat('l'),
            'rombel' => $args['rombel'] ?? 'semua kelas',
            'count' => count($items),
            'items' => $items,
            'note' => $items === [] ? 'Tidak ada jadwal yang cocok pada tanggal ini.' : 'Jadwal pelajaran pada tanggal tersebut.',
        ];
    }

    /**
     * Ringkasan jadwal sekolah hari ini untuk system prompt. Detail tetap
     * diambil lewat tool, jadi daftar panjang tidak memenuhi prompt.
     *
     * @return array<string, mixed>
     */
    private function schoolScheduleSummary(): array
    {
        $schedule = $this->classSchedule(['limit' => 500]);
        $items = is_array($schedule['items'] ?? null) ? $schedule['items'] : [];

        return [
            'date' => $schedule['date'] ?? now()->toDateString(),
            'day' => $schedule['day'] ?? now()->locale('id')->translatedFormat('l'),
            'count' => (int) ($schedule['count'] ?? 0),
            'classes' => collect($items)->pluck('class')->filter()->unique()->take(15)->values()->all(),
            'items' => array_slice($items, 0, 8),
            'note' => 'Ringkasan jadwal sekolah; detail per kelas tersedia lewat tool.',
        ];
    }

    private function resolveScheduleDate(?string $date = null, ?string $dayName = null): Carbon
    {
        if (filled($date)) {
            try {
                return Carbon::parse($date);
            } catch (\Throwable) {
                // fallback ke hari ini
            }
        }

        if (filled($dayName)) {
            $map = [
                'senin' => 1, 'monday' => 1,
                'selasa' => 2, 'tuesday' => 2,
                'rabu' => 3, 'wednesday' => 3,
                'kamis' => 4, 'thursday' => 4,
                'jumat' => 5, 'friday' => 5,
                'sabtu' => 6, 'saturday' => 6,
                'minggu' => 7, 'sunday' => 7,
            ];

            $target = $map[strtolower(trim($dayName))] ?? null;

            if ($target) {
                $today = now();
                $diff = $target - $today->dayOfWeekIso;

                return $today->copy()->addDays($diff);
            }
        }

        return now();
    }

    /**
     * @return array<string, mixed>
     */
    private function scheduleRow(JadwalKbm $schedule, bool $withTeacher): array
    {
        $row = [
            'time' => trim(($schedule->start_time ?? '').' - '.($schedule->end_time ?? ''), ' -'),
            'subject' => $schedule->subject?->name,
            'class' => $schedule->studyGroup?->name,
            'grade_level' => $schedule->studyGroup?->gradeLevel?->name,
            'room' => $schedule->room,
        ];

        if ($withTeacher) {
            $row['teacher'] = $schedule->teacher?->name;
        }

        return $row;
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function gradesSummary(array $args): array
    {
        if (! $this->canViewStudents && ! $this->isBroad) {
            return ['error' => 'Anda tidak berwenang mengakses data nilai santri.'];
        }

        if (! $this->isBroad && $this->myStudyGroupIds === []) {
            return ['error' => 'Data nilai hanya tersedia untuk kelas yang Anda ampu. Hubungi admin untuk akses lebih luas.'];
        }

        $query = DB::table('admin_nilai_sumatif as n')
            ->join('teacher_admin_books as b', 'b.id', '=', 'n.admin_book_id')
            ->join('subjects as s', 's.id', '=', 'b.subject_id')
            ->join('study_groups as g', 'g.id', '=', 'b.study_group_id')
            ->leftJoin('students as st', 'st.id', '=', 'n.student_id')
            ->leftJoin('academic_years as ay', 'ay.id', '=', 'b.academic_year_id')
            ->when($this->schoolId && ! $this->global, fn ($q) => $q->where('b.school_id', $this->schoolId))
            ->whereNotNull('n.nr_final');

        if (! empty($args['semester']) && in_array($args['semester'], ['ganjil', 'genap'], true)) {
            $query->where('n.semester', $args['semester']);
        }
        if (! empty($args['academic_year'])) {
            $query->where('ay.name', 'like', '%'.$args['academic_year'].'%');
        }
        if (! empty($args['subject'])) {
            $query->where(function ($q) use ($args) {
                $q->where('s.name', 'like', '%'.$args['subject'].'%')
                    ->orWhere('s.code', 'like', '%'.$args['subject'].'%');
            });
        }
        if (! empty($args['rombel'])) {
            $query->where(function ($q) use ($args) {
                $q->where('g.name', 'like', '%'.$args['rombel'].'%')
                    ->orWhere('g.code', 'like', '%'.$args['rombel'].'%');
            });
        }
        if (! empty($args['grade_level'])) {
            $query->join('grade_levels as gl', 'gl.id', '=', 'g.grade_level_id')
                ->where(function ($q) use ($args) {
                    $q->where('gl.name', 'like', '%'.$args['grade_level'].'%');
                    if (is_numeric($args['grade_level'])) {
                        $q->orWhere('gl.level', (int) $args['grade_level']);
                    }
                });
        }

        $this->applyDefaultGroupScope($query, 'b.study_group_id');

        $limit = max(1, min(50, (int) ($args['limit'] ?? 15)));

        $rows = (clone $query)
            ->selectRaw('s.name as mapel, g.name as rombel, ROUND(AVG(n.nr_final), 2) as rata_rata, COUNT(n.id) as jumlah_nilai')
            ->groupBy('s.name', 'g.name')
            ->orderByDesc('rata_rata')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'mapel' => (string) $row->mapel,
                'rombel' => (string) $row->rombel,
                'rata_rata' => (float) $row->rata_rata,
                'jumlah_nilai' => (int) $row->jumlah_nilai,
            ])
            ->all();

        $detail = [];
        if ($this->canViewStudents) {
            $detail = (clone $query)
                ->select('st.name as santri', 'st.nis', 'g.name as rombel', 's.name as mapel', 'n.semester', 'n.nr_final')
                ->orderBy('st.name')
                ->limit(min($limit, 20))
                ->get()
                ->map(fn ($row) => [
                    'santri' => (string) ($row->santri ?? '-'),
                    'nis' => (string) ($row->nis ?? '-'),
                    'rombel' => (string) ($row->rombel ?? '-'),
                    'mapel' => (string) ($row->mapel ?? '-'),
                    'semester' => (string) ($row->semester ?? '-'),
                    'nilai' => (float) ($row->nr_final ?? 0),
                ])
                ->all();
        }

        return [
            'rows' => $rows,
            'detail' => $detail,
            'total_rows' => count($rows),
            'note' => $detail === []
                ? 'Agregat nilai per mata pelajaran; rincian santri memerlukan izin student_view.'
                : 'Agregat dan rincian nilai sesuai izin pengguna.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ljkSummary(): array
    {
        $count = function (string $table, ?string $schoolColumn = null): int {
            if (! Schema::hasTable($table)) {
                return 0;
            }

            $query = DB::table($table);
            if ($schoolColumn && $this->schoolId && ! $this->global && Schema::hasColumn($table, $schoolColumn)) {
                $query->where($schoolColumn, $this->schoolId);
            }

            return $query->count();
        };

        return [
            'exams' => $count('ljk_exams', 'school_id'),
            'participants' => $count('ljk_participants'),
            'sheets' => $count('ljk_sheets'),
            'scans' => $count('ljk_scans'),
            'sync_logs' => $count('ljk_sync_logs'),
            'note' => 'Ringkasan jumlah data LJK.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function academicOverview(): array
    {
        return [
            'students_total' => $this->studentQuery()->count(),
            'students_active' => $this->studentQuery()->where('status', 'active')->count(),
            'gtk_active' => $this->gtkQuery()->whereNotIn('status_kepegawaian', ['Berhenti', 'Wafat', 'Pensiun'])->distinct('user_id')->count('user_id'),
            'rombel_active' => $this->studyGroupQuery()->where('is_active', true)->count(),
            'subjects_active' => $this->subjectQuery()->where('is_active', true)->count(),
            'grade_levels' => $this->gradeLevelQuery()->count(),
            'bank_soal' => $this->bankSoalQuery()->count(),
            'soal' => $this->soalQuery()->count(),
            'raport' => $this->raportSummary(),
            'tahfidz' => $this->tahfidzCounts(),
            'note' => 'Semua angka agregat; tanpa data pribadi.',
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function listMasterData(array $args): array
    {
        $type = $args['type'] ?? 'subjects';

        $items = match ($type) {
            'subjects' => $this->subjectQuery()->where('is_active', true)->orderBy('name')->limit(100)->get(['name', 'code'])->toArray(),
            'grade_levels' => $this->gradeLevelQuery()->orderBy('level')->limit(100)->get(['name', 'level', 'fase'])->toArray(),
            'rombel' => $this->studyGroupQuery()->where('is_active', true)->orderBy('name')->limit(100)->get(['name', 'code'])->toArray(),
            'academic_years' => AcademicYear::query()->orderByDesc('start_date')->limit(100)->get(['name', 'semester', 'is_active'])->toArray(),
            default => [],
        };

        return ['type' => $type, 'count' => count($items), 'items' => $items];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function searchStudents(array $args): array
    {
        if (! $this->canViewStudents) {
            return ['error' => 'Anda tidak berwenang mengakses data pribadi santri.'];
        }

        $query = $this->studentQuery();
        $search = trim((string) ($args['query'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('name_ar', 'like', '%'.$search.'%')
                    ->orWhere('nis', 'like', '%'.$search.'%')
                    ->orWhere('nisn', 'like', '%'.$search.'%');
            });
        }

        if (! empty($args['gender']) && in_array($args['gender'], ['L', 'P'], true)) {
            $query->where('gender', $args['gender']);
        }
        if (! empty($args['status'])) {
            $query->where('status', $args['status']);
        }

        $groupIds = $this->resolveStudyGroupIds($args);
        if ($groupIds !== null) {
            if ($groupIds === []) {
                return ['count' => 0, 'items' => [], 'note' => 'Tidak ada rombel yang cocok.'];
            }
            $query->whereHas('classHistories', fn (Builder $q) => $q->whereIn('study_group_id', $groupIds)->where('is_active', true));
        }

        $limit = max(1, min(20, (int) ($args['limit'] ?? 10)));
        $students = $query
            ->with([
                'school:id,name',
                'classHistories' => fn ($q) => $q->where('is_active', true)
                    ->with(['studyGroup:id,name,grade_level_id', 'studyGroup.gradeLevel:id,name'])
                    ->orderByDesc('created_at'),
            ])
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return [
            'count' => $students->count(),
            'items' => $students->map(fn (Student $student) => [
                'id' => $student->id,
                'name' => $student->name,
                'name_ar' => $student->name_ar,
                'nis' => $student->nis,
                'nisn' => $student->nisn,
                'gender' => $student->gender,
                'status' => $student->status,
                'school' => $student->school?->name,
                'rombel' => $student->classHistories->first()?->studyGroup?->name,
                'address' => $student->address,
            ])->all(),
            'note' => 'Data pribadi. Gunakan hanya untuk tugas yang sah.',
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function studentDetail(array $args): array
    {
        if (! $this->canViewStudents) {
            return ['error' => 'Anda tidak berwenang mengakses data pribadi santri.'];
        }

        $student = $this->findStudent($args);
        if (! $student) {
            return ['error' => 'Santri tidak ditemukan. Sertakan nama, NIS, NISN, atau ID yang valid.'];
        }

        $student->load([
            'school:id,name',
            'classHistories' => fn ($q) => $q->with([
                'studyGroup:id,name,grade_level_id',
                'studyGroup.gradeLevel:id,name',
                'academicYear:id,name',
            ])->orderByDesc('created_at'),
        ]);

        $attendance = StudentAttendance::query()
            ->where('student_id', $student->id)
            ->whereBetween('attendance_date', [now()->subDays(29)->toDateString(), now()->toDateString()])
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $raports = RaportRegistration::query()
            ->where('student_id', $student->id)
            ->with(['academicYear:id,name', 'studyGroup:id,name'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn (RaportRegistration $raport) => [
                'academic_year' => $raport->academicYear?->name,
                'semester' => $raport->semester,
                'rombel' => $raport->studyGroup?->name,
                'status' => $raport->status,
                'published' => (bool) $raport->is_published,
                'final_score' => $raport->final_score,
                'predicate' => $raport->predicate,
            ])->all();

        return [
            'identity' => [
                'id' => $student->id,
                'name' => $student->name,
                'name_ar' => $student->name_ar,
                'nis' => $student->nis,
                'nisn' => $student->nisn,
                'gender' => $student->gender,
                'status' => $student->status,
                'birth_place' => $student->birth_place,
                'birth_date' => $student->birth_date?->toDateString(),
                'religion' => $student->religion,
                'address' => $student->address,
                'rt' => $student->rt,
                'rw' => $student->rw,
                'hamlet' => $student->hamlet,
                'postal_code' => $student->postal_code,
                'phone' => $student->phone,
                'mobile_phone' => $student->mobile_phone,
                'email' => $student->email,
                'school' => $student->school?->name,
                'entry_date' => $student->entry_date?->toDateString(),
                'father_name' => $student->father_name,
                'mother_name' => $student->mother_name,
                'guardian_name' => $student->guardian_name,
            ],
            'class_history' => $student->classHistories->map(fn ($history) => [
                'rombel' => $history->studyGroup?->name,
                'grade_level' => $history->studyGroup?->gradeLevel?->name,
                'academic_year' => $history->academicYear?->name,
                'is_active' => (bool) $history->is_active,
                'join_date' => $history->join_date?->toDateString(),
                'leave_date' => $history->leave_date?->toDateString(),
            ])->all(),
            'attendance_30_days' => [
                'hadir' => (int) ($attendance['hadir'] ?? 0),
                'izin' => (int) ($attendance['izin'] ?? 0),
                'sakit' => (int) ($attendance['sakit'] ?? 0),
                'alpa' => (int) ($attendance['alpa'] ?? 0),
            ],
            'raport' => $raports,
            'note' => 'NIK/KK, rekening, data kesehatan, dan data sensitif lain tidak ditampilkan.',
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function searchGtk(array $args): array
    {
        if (! $this->canViewGtk) {
            return ['error' => 'Anda tidak berwenang mengakses data GTK.'];
        }

        $query = User::query()->whereHas('employment', function (Builder $q) {
            $q->when($this->schoolId && ! $this->global, fn (Builder $e) => $e->where('school_id', $this->schoolId));
        });

        $search = trim((string) ($args['query'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhereHas('employment', fn (Builder $e) => $e->where('nupy', 'like', '%'.$search.'%'));
            });
        }

        foreach (['jenis_gtk', 'jabatan', 'position_type'] as $field) {
            if (! empty($args[$field])) {
                $query->whereHas('employment', fn (Builder $q) => $q->where($field, 'like', '%'.$args[$field].'%'));
            }
        }

        $limit = max(1, min(20, (int) ($args['limit'] ?? 10)));
        $users = $query
            ->with(['employment' => fn ($q) => $q->with('school:id,name')])
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return [
            'count' => $users->count(),
            'items' => $users->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'nupy' => $user->employment?->nupy,
                'jenis_gtk' => $user->employment?->jenis_gtk,
                'jabatan' => $user->employment?->jabatan,
                'position_type' => $user->employment?->position_type,
                'status_kepegawaian' => $user->employment?->status_kepegawaian,
                'school' => $user->employment?->school?->name,
            ])->all(),
            'note' => 'Data pribadi GTK. Gunakan hanya untuk tugas yang sah.',
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function gtkDetail(array $args): array
    {
        if (! $this->canViewGtkDetail) {
            return ['error' => 'Anda tidak berwenang mengakses detail GTK.'];
        }

        $user = $this->findGtk($args);
        if (! $user) {
            return ['error' => 'GTK tidak ditemukan. Sertakan nama, NUPY, atau ID yang valid.'];
        }

        $user->load([
            'gtkProfile.addresses',
            'contact',
            'employments' => fn ($q) => $q->with(['school:id,name', 'academicYear:id,name'])->orderByDesc('created_at'),
        ]);

        $profile = $user->gtkProfile;
        $contact = $user->contact;

        return [
            'identity' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'tempat_lahir' => $profile?->tempat_lahir,
                'tanggal_lahir' => $profile?->tanggal_lahir?->toDateString(),
                'jenis_kelamin' => $profile?->jenis_kelamin,
                'agama' => $profile?->agama,
                'status_perkawinan' => $profile?->status_perkawinan,
            ],
            'contact' => [
                'no_hp' => $contact?->no_hp,
                'no_whatsapp' => $contact?->no_whatsapp,
                'kontak_darurat' => $contact?->kontak_darurat,
            ],
            'addresses' => $profile?->addresses->map(fn ($address) => [
                'type' => $address->type,
                'jalan' => $address->jalan,
                'rt_rw' => $address->rt_rw,
                'dusun' => $address->dusun,
                'desa' => $address->desa,
                'kecamatan' => $address->kecamatan,
                'kab_kota' => $address->kab_kota,
                'provinsi' => $address->provinsi,
                'kode_pos' => $address->kode_pos,
            ])->all() ?? [],
            'employments' => $user->employments->map(fn (GtkEmployment $employment) => [
                'school' => $employment->school?->name,
                'academic_year' => $employment->academicYear?->name,
                'nupy' => $employment->nupy,
                'jenis_gtk' => $employment->jenis_gtk,
                'jabatan' => $employment->jabatan,
                'position_type' => $employment->position_type,
                'status_kepegawaian' => $employment->status_kepegawaian,
                'tmt' => $employment->tmt?->toDateString(),
            ])->all(),
            'note' => 'NIK/KK/NPWP, gaji, dan data kesehatan tidak ditampilkan.',
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function findStudent(array $args): ?Student
    {
        $query = $this->studentQuery();

        if (! empty($args['student_id'])) {
            return $query->whereKey($args['student_id'])->first();
        }
        if (! empty($args['nis'])) {
            return $query->where('nis', $args['nis'])->first();
        }
        if (! empty($args['nisn'])) {
            return $query->where('nisn', $args['nisn'])->first();
        }
        if (! empty($args['name'])) {
            return $query->where(fn (Builder $q) => $q->where('name', 'like', '%'.$args['name'].'%')->orWhere('name_ar', 'like', '%'.$args['name'].'%'))->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function findGtk(array $args): ?User
    {
        if (! empty($args['user_id'])) {
            return User::query()->find($args['user_id']);
        }
        if (! empty($args['nupy'])) {
            return User::query()->whereHas('employment', fn (Builder $q) => $q->where('nupy', $args['nupy']))->first();
        }
        if (! empty($args['name'])) {
            $search = (string) $args['name'];

            return User::query()
                ->where(fn (Builder $q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'))
                ->whereHas('employment')
                ->first();
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function countMyStudents(): array
    {
        if ($this->myStudyGroupIds === []) {
            return ['error' => 'Tidak ada kelas yang ditugaskan kepada pengguna ini.'];
        }

        $histories = StudentClassHistory::query()
            ->whereIn('study_group_id', $this->myStudyGroupIds)
            ->where('is_active', true)
            ->whereHas('student', fn (Builder $q) => $q->where('status', 'active'))
            ->with(['student:id,name,status', 'studyGroup:id,name'])
            ->get();

        $uniqueStudents = $histories->pluck('student_id')->unique()->count();

        $classes = $histories->groupBy('study_group_id')->map(function ($rows) {
            return [
                'rombel' => $rows->first()->studyGroup?->name,
                'count' => $rows->pluck('student_id')->unique()->count(),
            ];
        })->values()->all();

        return [
            'total_active_students' => $uniqueStudents,
            'classes' => $classes,
            'note' => 'Dihitung dari penugasan mengajar dan riwayat kelas aktif.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function listMyClasses(): array
    {
        if ($this->myStudyGroupIds === []) {
            return ['error' => 'Tidak ada kelas yang ditugaskan kepada pengguna ini.'];
        }

        return [
            'academic_year' => $this->myTeaching['academic_year'] ?? null,
            'classes' => $this->myTeaching['classes'] ?? [],
            'subjects' => $this->myTeaching['subjects'] ?? [],
            'note' => 'Kelas dan mapel yang diampu pengguna saat ini.',
        ];
    }

    private function raportSummary(): array
    {
        $query = RaportRegistration::query();

        if ($this->schoolId && ! $this->global) {
            $query->whereHas('studyGroup', fn (Builder $q) => $q->where('school_id', $this->schoolId));
        }

        $byStatus = (clone $query)->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status')->all();

        return [
            'total' => (clone $query)->count(),
            'published' => (clone $query)->where('is_published', true)->count(),
            'by_status' => $byStatus,
        ];
    }

    private function tahfidzCounts(): array
    {
        $count = function (string $table, ?string $column = null): int {
            if (! Schema::hasTable($table)) {
                return 0;
            }

            $query = DB::table($table);
            if ($column && $this->schoolId && ! $this->global && Schema::hasColumn($table, $column)) {
                $query->where($column, $this->schoolId);
            }

            return $query->count();
        };

        return [
            'groups' => $count('tahfidz_groups', 'school_id'),
            'members' => $count('tahfidz_group_members'),
            'targets' => $count('tahfidz_student_targets'),
            'setoran' => $count('tahfidz_setorans'),
            'evaluations' => $count('tahfidz_evaluations'),
        ];
    }

    /**
     * Batasi query ke kelas yang diampu untuk pengguna non-broad.
     */
    private function applyDefaultGroupScope($query, string $column = 'study_group_id'): void
    {
        if (! $this->isBroad && $this->myStudyGroupIds !== []) {
            $query->whereIn($column, $this->myStudyGroupIds);
        }
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<int, string>|null
     */
    private function resolveStudyGroupIds(array $args): ?array
    {
        if (empty($args['grade_level']) && empty($args['rombel']) && empty($args['academic_year'])) {
            return null;
        }

        $query = $this->studyGroupQuery();

        if (! empty($args['academic_year'])) {
            $query->whereHas('academicYear', fn (Builder $q) => $q->where('name', 'like', '%'.$args['academic_year'].'%'));
        }
        if (! empty($args['grade_level'])) {
            $query->whereHas('gradeLevel', fn (Builder $q) => $this->filterGradeLevel($q, $args['grade_level']));
        }
        if (! empty($args['rombel'])) {
            $query->where(fn (Builder $q) => $q->where('name', 'like', '%'.$args['rombel'].'%')->orWhere('code', 'like', '%'.$args['rombel'].'%'));
        }

        return $query->pluck('id')->all();
    }

    private function filterGradeLevel(Builder $query, string $value): Builder
    {
        return $query->where(function (Builder $q) use ($value) {
            $q->where('name', 'like', '%'.$value.'%');
            if (is_numeric($value)) {
                $q->orWhere('level', (int) $value);
            }
        });
    }

    private function studentQuery(): Builder
    {
        return Student::query()->when($this->schoolId && ! $this->global, fn (Builder $q) => $q->where('school_id', $this->schoolId));
    }

    private function gtkQuery(): Builder
    {
        return GtkEmployment::query()->when($this->schoolId && ! $this->global, fn (Builder $q) => $q->where('school_id', $this->schoolId));
    }

    private function studyGroupQuery(): Builder
    {
        return StudyGroup::query()->when($this->schoolId && ! $this->global, fn (Builder $q) => $q->where('school_id', $this->schoolId));
    }

    private function subjectQuery(): Builder
    {
        return Subject::query()->when($this->schoolId && ! $this->global, fn (Builder $q) => $q->where('school_id', $this->schoolId));
    }

    private function gradeLevelQuery(): Builder
    {
        return GradeLevel::query()->when($this->schoolId && ! $this->global, fn (Builder $q) => $q->where('school_id', $this->schoolId));
    }

    private function bankSoalQuery(): Builder
    {
        return BankSoal::query()->when($this->schoolId && ! $this->global, fn (Builder $q) => $q->where('school_id', $this->schoolId));
    }

    private function soalQuery(): Builder
    {
        return Soal::query()->when($this->schoolId && ! $this->global, fn (Builder $q) => $q->whereHas('bankSoal', fn (Builder $b) => $b->where('school_id', $this->schoolId)));
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function tool(string $name, string $description, array $properties): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => $description,
                'parameters' => [
                    'type' => 'object',
                    'properties' => $properties,
                    'additionalProperties' => false,
                ],
            ],
        ];
    }
}
