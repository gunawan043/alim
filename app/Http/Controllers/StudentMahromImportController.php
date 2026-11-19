<?php

namespace App\Http\Controllers;

use App\Exports\StudentMahromTemplateExport;
use App\Imports\StudentMahromImport;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudentMahrom;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Modul import data mahrom/wali santri.
 *
 * Scope akses mengikuti modul Data Mahrom:
 *  - admin/struktural: seluruh santri pada konteks sekolah;
 *  - wali kelas: hanya santri pada rombel yang diwalikan;
 *  - user asrama: hanya santri yang mondok di asrama yang dapat diakses.
 */
class StudentMahromImportController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $scope = $this->resolveScope($request);

        $studentIds = (clone $scope['students'])->select('id');

        return view('students.mahroms.import', [
            'userId' => $userId,
            'studentCount' => (clone $scope['students'])->count(),
            'mahromCount' => StudentMahrom::whereIn('student_id', $studentIds)->count(),
            'withoutPhoneCount' => StudentMahrom::whereIn('student_id', $studentIds)
                ->where(fn ($q) => $q->whereNull('phone')->orWhere('phone', ''))
                ->count(),
        ]);
    }

    public function template(Request $request, string $userId)
    {
        $this->resolveScope($request); // otorisasi

        return (new StudentMahromTemplateExport)
            ->download('template_import_mahrom_'.now()->format('Ymd').'.xlsx');
    }

    public function store(Request $request, string $userId)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file.required' => 'File import wajib dipilih.',
            'file.mimes' => 'File harus berformat .xlsx, .xls, atau .csv.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        $scope = $this->resolveScope($request);

        $import = new StudentMahromImport(
            schoolId: $scope['school_id'],
            allowedStudentIds: $scope['student_ids'],
        );

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return back()
                ->with('error', 'Import gagal: '.$e->getMessage())
                ->withInput();
        }

        $message = "Import mahrom selesai: {$import->created} baru, {$import->updated} diperbarui, {$import->skipped} baris dilewati.";

        return redirect()
            ->route('user.students.mahroms.import', ['userId' => $userId])
            ->with($import->errors === [] ? 'success' : 'warning', $message)
            ->with('import_errors', $import->errors);
    }

    /**
     * @return array{school_id: ?string, student_ids: ?array, students: \Illuminate\Database\Eloquent\Builder}
     */
    private function resolveScope(Request $request): array
    {
        $user = $request->user();
        abort_unless($user, 403, 'Akses ditolak.');

        $schoolId = $request->attributes->get('schoolContextId');

        $students = Student::query()
            ->withoutGlobalScope('school_context')
            ->where('status', 'active')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        // Admin/struktural → seluruh sekolah.
        if ($user->isSystemAdmin()
            || $user->isSuperAdmin()
            || canPermission('student-all-access')
            || $user->hasAnyRole(['Pimpinan', 'Humas Personalia', 'Keuangan'])) {
            return ['school_id' => $schoolId, 'student_ids' => null, 'students' => $students];
        }

        // Wali kelas → rombel yang diwalikan.
        if ($user->isHomeroomTeacher()) {
            $groupIds = $user->homeroomStudyGroupIds();
            $yearId = AcademicYear::where('is_active', true)->value('id');

            $ids = StudentClassHistory::query()
                ->whereIn('study_group_id', $groupIds)
                ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
                ->pluck('student_id')
                ->unique()
                ->values()
                ->all();

            return [
                'school_id' => $schoolId,
                'student_ids' => $ids,
                'students' => $students->whereIn('id', $ids),
            ];
        }

        // User asrama → penghuni asrama yang dapat diakses.
        if ($user->isDormitoryUser()) {
            $dormitoryIds = $user->accessibleDormitoryIds();

            $ids = Student::query()
                ->withoutGlobalScope('school_context')
                ->whereHas('activeDormitoryResident', fn ($q) => $q->whereIn('dormitory_id', $dormitoryIds))
                ->pluck('id')
                ->all();

            return [
                'school_id' => $schoolId,
                'student_ids' => $ids,
                'students' => $students->whereIn('id', $ids),
            ];
        }

        return ['school_id' => $schoolId, 'student_ids' => null, 'students' => $students];
    }
}
