<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\DormitoryAttendance;
use App\Models\DormitoryResident;
use App\Models\DormitoryViolation;
use App\Models\NilaiFormatif;
use App\Models\NilaiSumatif;
use App\Models\StudentAchievement;
use App\Models\StudentAttendance;
use App\Models\StudentClassHistory;
use App\Models\StudentHealthRecord;
use App\Models\StudentMahrom;
use App\Models\TahfidzEvaluation;
use App\Models\TahfidzProgressRecap;
use App\Models\TahfidzSetoran;
use App\Models\TahfidzStudentTarget;
use App\Models\ViolationPoint;
use App\Models\WaliSantri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SantriDataController extends Controller
{
    public function __construct() {}

    // ── GET /api/mobile/v1/santri/{id}/attendance ──────────────────────────

    public function attendance(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $this->waliHasAccessTo($user->id, $id)) {
            return $this->notFoundError('Santri tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $date = $request->query('date', now()->format('Y-m-d'));

        $attendances = StudentAttendance::with('studyGroup:id,name')
            ->where('student_id', $id)
            ->where('attendance_date', $date)
            ->orderBy('arrival_time')
            ->get()
            ->map(fn ($att) => [
                'id' => $att->id,
                'date' => $att->attendance_date?->format('Y-m-d'),
                'status' => $att->status,
                'status_label' => $att->status_label,
                'arrival_time' => $att->arrival_time,
                'leave_time' => $att->leave_time,
                'study_group' => $att->studyGroup ? [
                    'id' => $att->studyGroup->id,
                    'name' => $att->studyGroup->name,
                ] : null,
                'notes' => $att->notes,
            ]);

        $monthStart = now()->parse($date)->startOfMonth()->toDateString();
        $monthEnd = now()->parse($date)->endOfMonth()->toDateString();

        $monthly = StudentAttendance::where('student_id', $id)
            ->whereBetween('attendance_date', [$monthStart, $monthEnd])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'attendances' => $attendances,
                'monthly_recap' => [
                    'month' => now()->parse($date)->format('Y-m'),
                    'hadir' => (int) ($monthly['hadir'] ?? 0),
                    'izin' => (int) ($monthly['izin'] ?? 0),
                    'sakit' => (int) ($monthly['sakit'] ?? 0),
                    'alpa' => (int) ($monthly['alpa'] ?? 0),
                    'total' => $monthly->sum(),
                ],
            ],
        ]);
    }

    // ── GET /api/mobile/v1/santri/{id}/attendance/history ──────────────────

    public function attendanceHistory(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $this->waliHasAccessTo($user->id, $id)) {
            return $this->notFoundError('Santri tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);

        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = now()->parse($start)->endOfMonth()->toDateString();

        $records = StudentAttendance::where('student_id', $id)
            ->whereBetween('attendance_date', [$start, $end])
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->map(fn ($att) => [
                'id' => $att->id,
                'date' => $att->attendance_date?->format('Y-m-d'),
                'status' => $att->status,
                'status_label' => $att->status_label,
                'arrival_time' => $att->arrival_time,
                'leave_time' => $att->leave_time,
                'notes' => $att->notes,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'month' => sprintf('%04d-%02d', $year, $month),
                'records' => $records,
                'total' => $records->count(),
            ],
        ]);
    }

    // ── GET /api/mobile/v1/santri/{id}/grades ──────────────────────────────

    public function grades(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $this->waliHasAccessTo($user->id, $id)) {
            return $this->notFoundError('Santri tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $activeYear = AcademicYear::where('is_active', true)->first();
        $yearId = $request->query('academic_year_id', $activeYear?->id);

        $sumatif = NilaiSumatif::with('adminBook:id,subject_id')
            ->where('student_id', $id)
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->orderBy('semester')
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'mata_pelajaran_id' => $n->adminBook?->subject_id,
                'semester' => $n->semester,
                's1' => $n->s1,
                's2' => $n->s2,
                's3' => $n->s3,
                's4' => $n->s4,
                's5' => $n->s5,
                's6' => $n->s6,
                'rs' => $n->rs,
                'sts' => $n->sts,
                'sas' => $n->sas,
                'rsa' => $n->rsa,
                'nr_murni' => $n->nr_murni,
                'nr_final' => $n->nr_final,
                'keterangan' => $n->ket,
            ]);

        $formatif = NilaiFormatif::with('adminBook:id,subject_id')
            ->where('student_id', $id)
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->orderBy('semester')
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'mata_pelajaran_id' => $n->adminBook?->subject_id,
                'semester' => $n->semester,
                'skor_lkpd' => $n->skor_lkpd,
                'skor_diskusi' => $n->skor_diskusi,
                'skor_kuis' => $n->skor_kuis,
                'skor_antarteman' => $n->skor_antarteman,
                'nr_final' => $n->nr_final,
                'keterangan' => $n->ket,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'academic_year' => $activeYear ? [
                    'id' => $activeYear->id,
                    'name' => $activeYear->name,
                    'semester' => $activeYear->semester,
                ] : null,
                'sumatif' => $sumatif,
                'formatif' => $formatif,
            ],
        ]);
    }

    // ── GET /api/mobile/v1/santri/{id}/classes ─────────────────────────────

    public function currentClasses(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $this->waliHasAccessTo($user->id, $id)) {
            return $this->notFoundError('Santri tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $current = StudentClassHistory::with(['studyGroup.gradeLevel', 'studyGroup.homeroomTeacher'])
            ->where('student_id', $id)
            ->where('is_active', true)
            ->orderByDesc('join_date')
            ->first();

        if (! $current) {
            return response()->json([
                'success' => true,
                'data' => [
                    'current' => null,
                    'message' => 'Santri belum terdaftar di kelas aktif.',
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'current' => [
                    'id' => $current->id,
                    'study_group' => [
                        'id' => $current->studyGroup?->id,
                        'name' => $current->studyGroup?->name,
                        'grade_level' => $current->studyGroup?->gradeLevel?->name ?? null,
                        'homeroom' => $current->studyGroup?->homeroomTeacher?->name ?? null,
                    ],
                    'academic_year' => $current->academic_year_id,
                    'attendance_number' => $current->attendance_number,
                    'join_date' => $current->join_date?->format('Y-m-d'),
                ],
            ],
        ]);
    }

    // ── GET /api/mobile/v1/santri/{id}/violations ──────────────────────────

    public function violations(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $this->waliHasAccessTo($user->id, $id)) {
            return $this->notFoundError('Santri tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $limit = min((int) $request->query('limit', 50), 100);
        $offset = max((int) $request->query('offset', 0), 0);

        $query = ViolationPoint::where('student_id', $id)
            ->orderByDesc('violation_date');

        $total = $query->count();
        $records = (clone $query)->offset($offset)->limit($limit)->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'date' => $v->violation_date?->format('Y-m-d'),
                'type' => $v->violation_type,
                'points' => $v->points,
                'description' => $v->description,
                'action_taken' => $v->action_taken,
            ]);

        $totalPoints = ViolationPoint::where('student_id', $id)->sum('points');

        return response()->json([
            'success' => true,
            'data' => [
                'records' => $records,
                'total' => $total,
                'total_points' => (int) $totalPoints,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }

    // ── GET /api/mobile/v1/santri/{id}/dormitory ───────────────────────────

    public function dormitoryInfo(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $this->waliHasAccessTo($user->id, $id)) {
            return $this->notFoundError('Santri tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $resident = DormitoryResident::with(['dormitory', 'room.wing'])
            ->where('student_id', $id)
            ->where('is_active', true)
            ->orderByDesc('check_in_date')
            ->first();

        if (! $resident) {
            return response()->json([
                'success' => true,
                'data' => ['assigned' => false],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'assigned' => true,
                'dormitory' => [
                    'id' => $resident->dormitory?->id,
                    'name' => $resident->dormitory?->name,
                ],
                'wing' => [
                    'id' => $resident->room?->wing?->id,
                    'name' => $resident->room?->wing?->name,
                ],
                'room' => [
                    'id' => $resident->room?->id,
                    'code' => $resident->room?->code,
                    'floor' => $resident->room?->floor,
                    'gender' => $resident->room?->gender,
                    'capacity' => $resident->room?->capacity,
                ],
                'bed_number' => $resident->bed_number,
                'check_in_date' => $resident->check_in_date?->format('Y-m-d'),
            ],
        ]);
    }

    // ── GET /api/mobile/v1/santri/{id}/dormitory-attendance ────────────────

    public function dormitoryAttendance(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $this->waliHasAccessTo($user->id, $id)) {
            return $this->notFoundError('Santri tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $date = $request->query('date', now()->format('Y-m-d'));
        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);
        $scope = $request->query('scope', 'day');

        if ($scope === 'month') {
            $start = sprintf('%04d-%02d-01', $year, $month);
            $end = now()->parse($start)->endOfMonth()->toDateString();

            $records = DormitoryAttendance::where('student_id', $id)
                ->whereBetween('attendance_date', [$start, $end])
                ->orderBy('attendance_date', 'desc')
                ->get()
                ->map(fn ($d) => $this->formatDormitoryAttendance($d));

            return response()->json([
                'success' => true,
                'data' => [
                    'scope' => 'month',
                    'month' => sprintf('%04d-%02d', $year, $month),
                    'records' => $records,
                ],
            ]);
        }

        $records = DormitoryAttendance::where('student_id', $id)
            ->where('attendance_date', $date)
            ->orderBy('session')
            ->get()
            ->map(fn ($d) => $this->formatDormitoryAttendance($d));

        return response()->json([
            'success' => true,
            'data' => [
                'scope' => 'day',
                'date' => $date,
                'records' => $records,
            ],
        ]);
    }

    private function formatDormitoryAttendance(DormitoryAttendance $d): array
    {
        return [
            'id' => $d->id,
            'date' => $d->attendance_date?->format('Y-m-d'),
            'session' => $d->session,
            'status' => $d->status,
            'notes' => $d->notes,
        ];
    }

    // ── GET /api/mobile/v1/santri/{id}/dormitory-violations ────────────────

    public function dormitoryViolations(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $this->waliHasAccessTo($user->id, $id)) {
            return $this->notFoundError('Santri tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $records = DormitoryViolation::where('student_id', $id)
            ->orderByDesc('violation_date')
            ->limit(50)
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'date' => $v->violation_date?->format('Y-m-d'),
                'category' => $v->violation_category,
                'type' => $v->violation_type,
                'description' => $v->description,
                'points' => $v->points,
                'action_taken' => $v->action_taken,
                'follow_up' => $v->follow_up,
                'parent_notified_at' => $v->parent_notified_at?->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => ['records' => $records],
        ]);
    }

    // ── GET /api/mobile/v1/santri/{id}/health ──────────────────────────────

    public function health(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $this->waliHasAccessTo($user->id, $id)) {
            return $this->notFoundError('Santri tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $record = StudentHealthRecord::where('student_id', $id)->first();

        if (! $record) {
            return response()->json([
                'success' => true,
                'data' => ['available' => false],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'available' => true,
                'blood_type' => $record->blood_type,
                'blood_type_label' => $record->blood_type_label,
                'height_cm' => $record->height_cm,
                'weight_kg' => $record->weight_kg,
                'bmi' => $record->bmi,
                'allergies' => $record->allergies,
                'chronic_diseases' => $record->chronic_diseases,
                'current_medications' => $record->current_medications,
                'emergency_notes' => $record->emergency_notes,
                'bpjs_number' => $record->bpjs_number,
                'insurance_provider' => $record->insurance_provider,
                'insurance_number' => $record->insurance_number,
                'last_physical_exam_date' => $record->last_physical_exam_date?->format('Y-m-d'),
            ],
        ]);
    }

    // ── GET /api/mobile/v1/santri/{id}/tahfidz ─────────────────────────────

    public function tahfidz(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $this->waliHasAccessTo($user->id, $id)) {
            return $this->notFoundError('Santri tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $activeYear = AcademicYear::where('is_active', true)->first();

        $target = TahfidzStudentTarget::where('student_id', $id)
            ->when($activeYear, fn ($q) => $q->where('academic_year_id', $activeYear->id))
            ->orderByDesc('created_at')
            ->first();

        $recap = TahfidzProgressRecap::where('student_id', $id)
            ->when($activeYear, fn ($q) => $q->where('academic_year_id', $activeYear->id))
            ->orderByDesc('semester')
            ->first();

        $recentSetoran = TahfidzSetoran::where('student_id', $id)
            ->orderByDesc('setoran_date')
            ->limit(10)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'date' => $s->setoran_date?->format('Y-m-d'),
                'type' => $s->setoran_type,
                'type_label' => $s->setoran_type_label,
                'juz' => $s->juz,
                'halaman_start' => $s->halaman_start,
                'halaman_end' => $s->halaman_end,
                'nilai_setoran' => $s->nilai_setoran,
                'status' => $s->status,
                'status_label' => $s->status_label,
                'capaian_target' => $s->capaian_target,
            ]);

        $evaluations = TahfidzEvaluation::where('student_id', $id)
            ->orderByDesc('evaluation_date')
            ->limit(5)
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'date' => $e->evaluation_date?->format('Y-m-d'),
                'type' => $e->evaluation_type,
                'type_label' => $e->evaluation_type_label,
                'juz_diuji' => $e->juz_diuji,
                'nilai_tahfizh' => $e->nilai_tahfizh,
                'nilai_tajwid' => $e->nilai_tajwid,
                'nilai_fashohah' => $e->nilai_fashohah,
                'nilai_keseluruhan' => $e->nilai_keseluruhan,
                'predikat' => $e->predikat,
                'predikat_label' => $e->predikat_label,
                'rekomendasi' => $e->rekomendasi,
                'status' => $e->status,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'academic_year' => $activeYear ? [
                    'id' => $activeYear->id,
                    'name' => $activeYear->name,
                    'semester' => $activeYear->semester,
                ] : null,
                'target' => $target ? [
                    'juz_start' => $target->juz_start,
                    'juz_end' => $target->juz_end,
                    'target_halaman' => $target->target_halaman,
                    'target_hadits' => $target->target_hadits,
                    'notes' => $target->notes,
                ] : null,
                'recap' => $recap ? [
                    'total_juz_ziyadah' => $recap->total_juz_ziyadah,
                    'total_halaman_ziyadah' => $recap->total_halaman_ziyadah,
                    'total_juz_murajaah' => $recap->total_juz_murajaah,
                    'total_halaman_murajaah' => $recap->total_halaman_murajaah,
                    'total_setoran' => $recap->total_setoran,
                    'total_hari_setoran' => $recap->total_hari_setoran,
                    'rata_rata_nilai' => $recap->rata_rata_nilai,
                    'pencapaian_target_persen' => $recap->pencapaian_target_persen,
                    'last_position_juz' => $recap->last_position_juz,
                    'last_position_halaman' => $recap->last_position_halaman,
                    'total_juz_completed' => $recap->total_juz_completed,
                    'hadits_count' => $recap->hadits_count,
                ] : null,
                'recent_setoran' => $recentSetoran,
                'evaluations' => $evaluations,
            ],
        ]);
    }

    // ── GET /api/mobile/v1/santri/{id}/achievements ────────────────────────

    public function achievements(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $this->waliHasAccessTo($user->id, $id)) {
            return $this->notFoundError('Santri tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $records = StudentAchievement::where('student_id', $id)
            ->orderByDesc('event_date')
            ->limit(50)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'type' => $a->achievement_type,
                'type_label' => $a->type_label,
                'event_name' => $a->event_name,
                'organizer' => $a->organizer,
                'level' => $a->level,
                'level_label' => $a->level_label,
                'position' => $a->position,
                'position_label' => $a->position_label,
                'event_date' => $a->event_date?->format('Y-m-d'),
                'event_location' => $a->event_location,
                'is_verified' => $a->is_verified,
                'certificate_url' => $a->certificate_url,
                'photo_url' => $a->photo_url,
            ]);

        return response()->json([
            'success' => true,
            'data' => ['records' => $records],
        ]);
    }

    // ── GET /api/mobile/v1/santri/{id}/mahroms ─────────────────────────────

    public function mahroms(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $this->waliHasAccessTo($user->id, $id)) {
            return $this->notFoundError('Santri tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $records = StudentMahrom::where('student_id', $id)
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'relationship' => $m->relationship,
                'relationship_label' => $m->relationship_text,
                'phone' => $m->phone,
                'address' => $m->address,
                'is_primary' => $m->is_primary,
                'photo_url' => $m->photo_url,
            ]);

        return response()->json([
            'success' => true,
            'data' => ['records' => $records],
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function waliHasAccessTo(string $userId, string $studentId): bool
    {
        // Tenant-scoped access check: the wali must have an active link to
        // the student in the active school context.
        $request = request();
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId === null) {
            return false;
        }

        return WaliSantri::where('user_id', $userId)
            ->where('student_id', $studentId)
            ->where('school_id', $schoolId)
            ->active()
            ->exists();
    }

    private function notFoundError(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'STUDENT_NOT_FOUND',
                'message' => $message,
            ],
        ], 404);
    }
}
