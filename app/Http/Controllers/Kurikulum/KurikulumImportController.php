<?php

namespace App\Http\Controllers\Kurikulum;

use App\Exports\KurikulumImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\KurikulumImport;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Subject;
use App\Services\KurikulumAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KurikulumImportController extends Controller
{
    public function __construct(private readonly KurikulumAccess $access) {}

    public function form(Request $request, string $userId)
    {
        $this->authorizeImport($request);

        $isGlobal = $request->attributes->get('isGlobalView') === true;
        $schoolContextId = $request->attributes->get('schoolContextId');

        $schools = $isGlobal
            ? School::orderBy('name')->get()
            : School::whereKey($schoolContextId)->get();

        $selectedSchoolId = $isGlobal
            ? ($request->filled('school_id') ? $request->school_id : $schoolContextId)
            : $schoolContextId;

        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('name')->get();
        $activeAy = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $selectedAyId = $request->input('academic_year_id', $activeAy?->id);
        $selectedSemester = $request->input('semester', $academicYears->firstWhere('id', $selectedAyId)?->semester ?? 'ganjil');
        if (! in_array($selectedSemester, ['ganjil', 'genap'], true)) {
            $selectedSemester = 'ganjil';
        }

        // Guru mapel: import dibatasi ke mapel yang diampu (SK mengajar aktif).
        // Tim kurikulum hanya mendapat lingkup penuh lewat menu jabatan.
        $broad = $this->access->isBroadScope($request->user(), $request);
        $taughtSubjectIds = $broad
            ? null
            : $this->access->taughtSubjectIds($request->user(), $selectedSchoolId, $selectedAyId);

        $taughtSubjects = $broad
            ? collect()
            : Subject::query()
                ->whereIn('id', $taughtSubjectIds ?? [])
                ->orderBy('name')
                ->get(['id', 'name', 'code']);

        return view('kurikulum.import', compact(
            'userId', 'schools', 'academicYears', 'selectedSchoolId', 'selectedAyId',
            'selectedSemester', 'isGlobal', 'broad', 'taughtSubjects'
        ));
    }

    public function template(Request $request, string $userId)
    {
        $this->authorizeImport($request);

        $school = $this->resolveSchool($request);
        abort_if(! $school, 422, 'Pilih satuan pendidikan terlebih dahulu.');

        $academicYear = $request->filled('academic_year_id')
            ? AcademicYear::find($request->academic_year_id)
            : (AcademicYear::where('is_active', true)->first() ?? AcademicYear::orderByDesc('name')->first());

        $semester = $request->input('semester', $academicYear?->semester ?? 'ganjil');
        if (! in_array($semester, ['ganjil', 'genap'], true)) {
            $semester = 'ganjil';
        }

        $broad = $this->access->isBroadScope($request->user(), $request);

        return (new KurikulumImportTemplateExport(
            $school,
            $academicYear,
            $semester,
            $broad ? null : $this->access->taughtSubjectIds($request->user(), $school->id, $academicYear?->id),
            $broad ? null : $request->user()
        ))->download();
    }

    public function store(Request $request, string $userId)
    {
        $this->authorizeImport($request);

        $isGlobal = $request->attributes->get('isGlobalView') === true;
        $schoolContextId = $request->attributes->get('schoolContextId');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'school_id' => $isGlobal ? 'required|exists:schools,id' : 'nullable',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'update_existing' => 'nullable|boolean',
        ], [
            'file.required' => 'File Excel wajib diunggah.',
            'file.mimes' => 'File harus berformat .xlsx, .xls, atau .csv.',
        ]);

        $school = $this->resolveSchool($request);
        abort_if(! $school, 422, 'Tidak dapat menentukan satuan pendidikan.');

        $academicYear = AcademicYear::findOrFail($request->input('academic_year_id'));

        // Kembali ke menu asal: menu jabatan memakai lingkup penuh.
        $formRoute = ['userId' => $userId] + ($this->access->isBroadScope($request->user(), $request) ? ['lingkup' => 'semua'] : []);

        try {
            $result = (new KurikulumImport(
                $school,
                $academicYear,
                $request->input('semester'),
                $request->boolean('update_existing', true),
                $request->user(),
                $this->allowedSubjectIds($request, $school->id, $academicYear->id)
            ))->import($request->file('file'));
        } catch (\Throwable $e) {
            Log::error('Import kurikulum gagal', [
                'user_id' => $request->user()?->id,
                'school_id' => $school->id,
                'academic_year_id' => $academicYear->id,
                'exception' => $e,
            ]);

            return redirect()
                ->route('user.kurikulum.import.form', $formRoute)
                ->with('error', 'File tidak dapat dibaca. Periksa format template lalu coba lagi.');
        }

        $stats = $result['stats'];
        $errors = $result['errors'];
        $warnings = $result['warnings'];
        $total = array_sum($stats);

        $message = $total > 0
            ? "Import Kurikulum selesai: {$stats['cp_created']} CP baru, {$stats['cp_updated']} CP diperbarui, {$stats['tp_created']} TP baru, {$stats['tp_updated']} TP diperbarui, {$stats['atp_created']} ATP baru, {$stats['atp_updated']} ATP diperbarui, {$stats['perangkat_created']} Perangkat baru, {$stats['perangkat_updated']} Perangkat diperbarui, {$stats['prota_created']} PROTA baru, {$stats['prota_updated']} PROTA diperbarui, {$stats['prosem_created']} PROSEM baru, {$stats['prosem_updated']} PROSEM diperbarui."
            : 'Tidak ada data yang berhasil diimport.';

        return redirect()
            ->route('user.kurikulum.import.form', $formRoute)
            ->with($total > 0 ? 'success' : 'error', $message)
            ->with('kurikulum_import_errors', $errors)
            ->with('kurikulum_import_warnings', $warnings)
            ->with('kurikulum_import_stats', $stats);
    }

    private function authorizeImport(Request $request): void
    {
        abort_unless(
            $this->access->canAccessKurikulumModule($request->user()),
            403,
            'Anda tidak memiliki akses untuk mengimport data Kurikulum.'
        );
    }

    /**
     * Mapel yang boleh diimport. null = tanpa batas (menu jabatan tim kurikulum).
     *
     * @return array<int, string>|null
     */
    private function allowedSubjectIds(Request $request, string $schoolId, ?string $academicYearId): ?array
    {
        if ($this->access->isBroadScope($request->user(), $request)) {
            return null;
        }

        return $this->access->taughtSubjectIds($request->user(), $schoolId, $academicYearId);
    }

    private function resolveSchool(Request $request): ?School
    {
        $isGlobal = $request->attributes->get('isGlobalView') === true;
        $schoolContextId = $request->attributes->get('schoolContextId');

        if ($isGlobal) {
            $schoolId = $request->input('school_id');

            return $schoolId ? School::find($schoolId) : ($schoolContextId ? School::find($schoolContextId) : null);
        }

        return $schoolContextId ? School::find($schoolContextId) : null;
    }
}
