<?php

namespace App\Services\Ai;

use App\Models\AcademicYear;
use App\Models\BankSoal;
use App\Models\GradeLevel;
use App\Models\GtkEmployment;
use App\Models\RaportRegistration;
use App\Models\Soal;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudyGroup;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Akses data read-only untuk AI Chat.
 *
 * Prinsip:
 * - hanya agregat / master data non-sensitif;
 * - tidak pernah mengembalikan nama santri, NIS/NISN, alamat, kontak,
 *   data kesehatan, keluarga, bank, gaji, atau kredensial;
 * - selalu dibatasi school context pengguna (kecuali global view).
 */
class AiDataToolService
{
    private ?string $schoolId = null;

    private bool $global = false;

    /**
     * Definisi tool gaya OpenAI Chat Completions.
     *
     * @return array<int, array<string, mixed>>
     */
    public function definitions(): array
    {
        return [
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
            $this->tool('academic_overview', 'Ringkasan agregat akademik: santri, GTK, rombel, mapel, bank soal, rapor, dan tahfidz.', []),
            $this->tool('list_master_data', 'Daftar nama master data non-sensitif: subjects, grade_levels, rombel, academic_years.', [
                'type' => ['type' => 'string', 'enum' => ['subjects', 'grade_levels', 'rombel', 'academic_years']],
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @param  array{school_id?: ?string, global?: bool}  $context
     * @return array<string, mixed>
     */
    public function execute(string $name, array $args, array $context): array
    {
        $this->schoolId = $context['school_id'] ?? null;
        $this->global = (bool) ($context['global'] ?? false);

        try {
            return match ($name) {
                'count_students' => $this->countStudents($args),
                'count_gtk' => $this->countGtk($args),
                'count_rombel' => $this->countRombel($args),
                'attendance_summary' => $this->attendanceSummary($args),
                'academic_overview' => $this->academicOverview(),
                'list_master_data' => $this->listMasterData($args),
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
        $this->schoolId = $context['school_id'] ?? null;
        $this->global = (bool) ($context['global'] ?? false);

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
            'note' => 'Hanya agregat; identitas pribadi tidak tersedia untuk AI.',
        ];
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

        if (($args['active_only'] ?? true) === true) {
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

        if (($args['active_only'] ?? true) === true) {
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

        if (($args['include_names'] ?? false) === true) {
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
