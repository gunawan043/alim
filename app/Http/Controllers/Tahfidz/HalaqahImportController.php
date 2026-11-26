<?php

namespace App\Http\Controllers\Tahfidz;

use App\Exports\TahfidzHalaqahTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\School;
use App\Services\Tahfidz\HalaqahImportService;
use App\Services\TahfidzAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Impor halaqah dari Excel: form → pratinjau → simpan.
 *
 * Rencana hasil pratinjau disimpan sementara (15 menit, per pengguna) sehingga
 * tahap simpan memakai perencanaan yang sudah dilihat pengguna, bukan membaca
 * ulang berkas yang mungkin sudah berbeda.
 */
class HalaqahImportController extends Controller
{
    private const PLAN_TTL_MINUTES = 15;

    public function __construct(
        private readonly TahfidzAccess $access,
        private readonly HalaqahImportService $import,
    ) {}

    public function form(Request $request, string $userId)
    {
        $this->authorizeImport($request);

        $schoolId = $this->schoolId($request);
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $activeYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $canChooseSchool = $this->access->canViewAllSchools($request->user());
        $schools = $canChooseSchool
            ? School::orderBy('name')->get(['id', 'name'])
            : School::whereKey($schoolId)->get(['id', 'name']);

        return view('tahfidz.halaqah.import', [
            'userId' => $userId,
            'schools' => $schools,
            'canChooseSchool' => $canChooseSchool,
            'schoolId' => $schoolId,
            'academicYears' => $academicYears,
            'academicYearId' => $request->input('academic_year_id', $activeYear?->id),
        ]);
    }

    public function template(Request $request, string $userId)
    {
        $this->authorizeImport($request);

        $school = $this->resolveSchool($request);
        abort_if(! $school, 422, 'Pilih satuan pendidikan terlebih dahulu.');

        $academicYear = $this->resolveAcademicYear($request, $school->id);

        return (new TahfidzHalaqahTemplateExport($school, $academicYear))->download();
    }

    /**
     * Pratinjau — TIDAK menulis ke database.
     */
    public function preview(Request $request, string $userId)
    {
        $this->authorizeImport($request);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'school_id' => 'required|exists:schools,id',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $school = $this->resolveSchool($request);
        abort_if(! $school, 422, 'Satuan pendidikan tidak ditemukan.');
        $this->assertSchoolAllowed($request, $school->id);

        $rows = $this->readSpreadsheet($request->file('file')->getRealPath());

        if ($rows === []) {
            return back()->with('error', 'Berkas kosong atau judul kolom tidak dikenali. Gunakan template yang disediakan.');
        }

        $plan = $this->import
            ->read($rows, $school, $request->academic_year_id)
            ->plan();

        Cache::put($this->planKey($request, $userId), $plan, now()->addMinutes(self::PLAN_TTL_MINUTES));

        return view('tahfidz.halaqah.import', [
            'userId' => $userId,
            'schools' => $this->access->canViewAllSchools($request->user())
                ? School::orderBy('name')->get(['id', 'name'])
                : School::whereKey($school->id)->get(['id', 'name']),
            'canChooseSchool' => $this->access->canViewAllSchools($request->user()),
            'schoolId' => $school->id,
            'academicYears' => AcademicYear::orderByDesc('start_date')->get(),
            'academicYearId' => $request->academic_year_id,
            'school' => $school,
            'plan' => $plan,
        ]);
    }

    /**
     * Simpan hasil pratinjau.
     */
    public function store(Request $request, string $userId)
    {
        $this->authorizeImport($request);

        $plan = Cache::get($this->planKey($request, $userId));

        if (! $plan) {
            return redirect()
                ->route('user.tahfidz.halaqah.import.form', ['userId' => $userId])
                ->with('error', 'Pratinjau sudah kedaluwarsa. Unggah ulang berkas untuk melihat hasil terbaru.');
        }

        $school = $this->resolveSchool($request);
        abort_if(! $school, 422, 'Satuan pendidikan tidak ditemukan.');

        $result = $this->import
            ->read([], $school, (string) $request->input('academic_year_id', ''))
            ->commit($plan['groups'], $request->user()?->id);

        Cache::forget($this->planKey($request, $userId));

        $message = "Impor selesai: {$result['created']} halaqah baru, {$result['updated']} diperbarui, {$result['members']} santri ditambahkan.";

        if ($result['failed'] !== []) {
            Log::warning('HalaqahImport: sebagian halaqah gagal disimpan', ['groups' => $result['failed']]);

            return redirect()
                ->route('user.tahfidz.halaqah.index', ['userId' => $userId])
                ->with('warning', $message.' Gagal: '.implode(', ', $result['failed']).'.');
        }

        return redirect()
            ->route('user.tahfidz.halaqah.index', ['userId' => $userId])
            ->with('success', $message);
    }

    /**
     * Baca berkas menjadi array asosiatif (baris pertama = nama kolom).
     *
     * @return array<int, array<string, mixed>>
     */
    private function readSpreadsheet(string $path): array
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $data = $sheet->toArray(null, true, true, false);

        if ($data === [] || count($data) < 2) {
            return [];
        }

        $headings = array_map(
            fn ($value) => strtolower(trim((string) $value)),
            array_shift($data)
        );

        $rows = [];

        foreach ($data as $line) {
            $row = [];

            foreach ($headings as $index => $heading) {
                if ($heading === '') {
                    continue;
                }

                $row[$heading] = $line[$index] ?? null;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function planKey(Request $request, string $userId): string
    {
        $school = $request->input('school_id', '-');
        $year = $request->input('academic_year_id', '-');

        return "tahfidz:halaqah-import:{$userId}:{$school}:{$year}";
    }

    private function resolveSchool(Request $request): ?School
    {
        $id = $request->input('school_id') ?: $this->schoolId($request);

        return $id ? School::find($id) : null;
    }

    private function resolveAcademicYear(Request $request, string $schoolId): ?AcademicYear
    {
        return $request->filled('academic_year_id')
            ? AcademicYear::find($request->input('academic_year_id'))
            : AcademicYear::where('is_active', true)->first();
    }

    private function assertSchoolAllowed(Request $request, string $schoolId): void
    {
        if ($this->access->canViewAllSchools($request->user())) {
            return;
        }

        abort_unless(
            (string) $schoolId === (string) $this->schoolId($request),
            403,
            'Anda hanya dapat mengimpor halaqah pada satuan pendidikan Anda.'
        );
    }

    private function authorizeImport(Request $request): void
    {
        $schoolId = $this->schoolId($request);

        abort_unless(
            $this->access->isSuperAdmin($request->user())
            || $this->access->isSchoolCoordinator($request->user(), $schoolId),
            403,
            'Hanya koordinator sekolah yang dapat mengimpor halaqah.'
        );
    }

    private function schoolId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId') ?: $request->user()?->employment?->school_id;
    }
}
