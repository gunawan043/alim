<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudentDapodikMapping;
use App\Models\StudyGroup;
use App\Services\SchoolUnitService;
use Illuminate\Http\Request;

/**
 * Export daftar santri untuk kebutuhan pemetaan Dapodik.
 *
 * Rombel operasional dan rombel Dapodik dipisahkan. Kolom Dapodik berasal
 * dari student_dapodik_mappings, bukan dari rombel operasional.
 */
class StudentDapodikExportController extends Controller
{
    public function index(Request $request, string $userId, SchoolUnitService $units)
    {
        $primarySchoolId = $units->primarySchoolId($request->attributes->get('schoolContextId') ?: $request->input('school_id'));
        abort_unless($primarySchoolId, 422, 'Unit induk belum terhubung.');

        $yearId = $request->input('academic_year_id')
            ?: AcademicYear::where('is_active', true)->value('id');
        $year = $yearId ? AcademicYear::find($yearId) : null;

        $search = trim((string) $request->input('search'));
        $studyGroupId = $request->input('study_group_id');
        $mappingStatus = $request->input('mapping_status');

        $baseQuery = Student::query()
            ->where('school_id', $primarySchoolId)
            ->where('status', 'active');

        // Statistik ringkas — dihitung dari populasi penuh, bukan hasil filter.
        $totalStudents = (clone $baseQuery)->count();
        $mappedCount = $yearId
            ? StudentDapodikMapping::query()
                ->where('academic_year_id', $yearId)
                ->whereIn('student_id', (clone $baseQuery)->select('id'))
                ->count()
            : 0;

        $studentsQuery = (clone $baseQuery)
            ->with([
                'classHistories' => function ($query) use ($yearId) {
                    $query->where('is_active', true)
                        ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
                        ->with(['studyGroup.school']);
                },
                'dapodikMappings' => fn ($query) => $query
                    ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId)),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('nisn', 'like', '%'.$search.'%')
                        ->orWhere('nis', 'like', '%'.$search.'%');
                });
            })
            ->when($studyGroupId, function ($query) use ($studyGroupId, $yearId) {
                $query->whereHas('classHistories', function ($q) use ($studyGroupId, $yearId) {
                    $q->where('study_group_id', $studyGroupId)
                        ->when($yearId, fn ($qq) => $qq->where('academic_year_id', $yearId));
                });
            })
            ->when($mappingStatus === 'mapped', function ($query) use ($yearId) {
                $query->whereHas('dapodikMappings', fn ($q) => $q->where('academic_year_id', $yearId));
            })
            ->when($mappingStatus === 'unmapped', function ($query) use ($yearId) {
                $query->whereDoesntHave('dapodikMappings', fn ($q) => $q->where('academic_year_id', $yearId));
            })
            ->orderBy('name');

        $students = $studentsQuery->paginate(50)->withQueryString();

        // Rombel operasional yang tersedia untuk filter (dari riwayat kelas
        // santri unit induk pada TA terpilih).
        $groupIds = StudentClassHistory::query()
            ->whereIn('student_id', (clone $baseQuery)->select('id'))
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->where('is_active', true)
            ->distinct()
            ->pluck('study_group_id');

        $studyGroups = StudyGroup::query()
            ->whereIn('id', $groupIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Mapping hanya untuk santri halaman ini; form menyimpan yang terlihat.
        $mappings = $students->getCollection()
            ->mapWithKeys(fn (Student $student) => [
                $student->id => $student->dapodikMappings->first(),
            ]);

        $school = School::find($primarySchoolId);
        $academicYears = AcademicYear::orderByDesc('start_date')->get();

        return view('students.dapodik-mapping', compact(
            'students', 'mappings', 'year', 'yearId', 'school', 'academicYears', 'userId',
            'studyGroups', 'studyGroupId', 'mappingStatus', 'search',
            'totalStudents', 'mappedCount'
        ));
    }

    public function save(Request $request, string $userId, SchoolUnitService $units)
    {
        $primarySchoolId = $units->primarySchoolId($request->attributes->get('schoolContextId') ?: $request->input('school_id'));
        abort_unless($primarySchoolId, 422, 'Unit induk belum terhubung.');

        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'mappings' => 'nullable|array',
            'mappings.*.dapodik_school_npsn' => 'nullable|string|max:20',
            'mappings.*.dapodik_rombel_name' => 'nullable|string|max:100',
            'mappings.*.dapodik_rombel_code' => 'nullable|string|max:50',
            'mappings.*.dapodik_level' => 'nullable|string|max:20',
        ]);

        $studentIds = Student::where('school_id', $primarySchoolId)->pluck('id')->all();

        foreach ((array) $data['mappings'] as $studentId => $values) {
            if (! in_array($studentId, $studentIds, true)) {
                continue;
            }

            $hasValue = collect($values)->filter(fn ($value) => filled($value))->isNotEmpty();
            if (! $hasValue) {
                StudentDapodikMapping::where('student_id', $studentId)
                    ->where('academic_year_id', $data['academic_year_id'])
                    ->delete();

                continue;
            }

            StudentDapodikMapping::updateOrCreate(
                ['student_id' => $studentId, 'academic_year_id' => $data['academic_year_id']],
                [
                    'dapodik_school_npsn' => $values['dapodik_school_npsn'] ?? null,
                    'dapodik_rombel_name' => $values['dapodik_rombel_name'] ?? null,
                    'dapodik_rombel_code' => $values['dapodik_rombel_code'] ?? null,
                    'dapodik_level' => $values['dapodik_level'] ?? null,
                    'is_active' => true,
                ],
            );
        }

        return back()->with('success', 'Mapping Dapodik berhasil disimpan.');
    }

    public function export(Request $request, string $userId, SchoolUnitService $units)
    {
        $contextSchoolId = $request->attributes->get('schoolContextId');
        $primarySchoolId = $units->primarySchoolId($contextSchoolId ?: $request->input('school_id'));

        abort_unless($primarySchoolId, 422, 'Unit induk belum terhubung.');

        $yearId = $request->input('academic_year_id')
            ?: AcademicYear::where('is_active', true)->value('id');

        $year = $yearId ? AcademicYear::find($yearId) : null;

        $mappings = StudentDapodikMapping::query()
            ->where('academic_year_id', $yearId)
            ->get()
            ->keyBy('student_id');

        $students = Student::query()
            ->with(['classHistories' => function ($query) use ($yearId) {
                $query->where('is_active', true)
                    ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
                    ->with(['studyGroup.school']);
            }])
            ->where('school_id', $primarySchoolId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $filename = 'Dapodik-'.($year?->name ?? 'semua').'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($students, $mappings) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'NISN', 'NIS', 'Nama', 'JK',
                'Unit Operasional', 'Rombel Operasional',
                'NPSN Dapodik', 'Rombel Dapodik', 'Kode Rombel Dapodik', 'Status Mapping',
            ]);

            foreach ($students as $student) {
                $history = $student->classHistories->first();
                $group = $history?->studyGroup;
                $mapping = $mappings->get($student->id);

                fputcsv($handle, [
                    $student->nisn,
                    $student->nis,
                    $student->name,
                    $student->gender,
                    $group?->school?->name,
                    $group?->name,
                    $mapping?->dapodik_school_npsn,
                    $mapping?->dapodik_rombel_name,
                    $mapping?->dapodik_rombel_code,
                    $mapping ? 'Terpetakan' : 'Belum dipetakan',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
