<?php

namespace App\Http\Controllers;

use App\Exports\StudentMahromTemplateExport;
use App\Imports\StudentMahromImport;
use App\Models\StudentMahrom;
use App\Services\Students\MahromAccessScope;
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
    public function __construct(private readonly MahromAccessScope $scope) {}

    public function index(Request $request, string $userId)
    {
        $scope = $this->scope->resolve($request->user(), $request->attributes->get('schoolContextId'));

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

    /**
     * Template kosong (header saja) — format lebar.
     */
    public function template(Request $request, string $userId)
    {
        $this->scope->resolve($request->user(), $request->attributes->get('schoolContextId')); // otorisasi

        $maxMahrom = (int) config('alim.max_mahrom', StudentMahrom::MAX_PER_STUDENT);

        return (new StudentMahromTemplateExport(filled: false, students: null, maxMahrom: $maxMahrom))
            ->download('template_import_mahrom_'.now()->format('Ymd').'.xlsx');
    }

    /**
     * Template terisi: informasi santri otomatis + data mahrom di kolom samping.
     */
    public function templateFilled(Request $request, string $userId)
    {
        $scope = $this->scope->resolve($request->user(), $request->attributes->get('schoolContextId'));
        $maxMahrom = (int) config('alim.max_mahrom', StudentMahrom::MAX_PER_STUDENT);

        $students = (clone $scope['students'])
            ->with([
                'currentClassHistory.studyGroup.gradeLevel',
                'activeDormitoryResident.room.wing',
                'activeDormitoryResident.dormitory:id,name',
                'mahroms' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('relationship'),
            ])
            ->preferredOrder()
            ->get();

        return (new StudentMahromTemplateExport(filled: true, students: $students, maxMahrom: $maxMahrom))
            ->download('data_mahrom_siap_edit_'.now()->format('Ymd').'.xlsx');
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

        $scope = $this->scope->resolve($request->user(), $request->attributes->get('schoolContextId'));

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
}
