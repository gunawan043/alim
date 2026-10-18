<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\NilaiSumatif;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\TahfidzCertificate;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzJuzProgress;
use App\Models\TahfidzProgressRecap;
use App\Models\TeacherAdminBook;
use App\Services\TahfidzAccess;
use App\Services\TahfidzRaporService;
use App\Services\TahfidzTasmiService;
use App\Support\AcademicNilai;
use Illuminate\Http\Request;

class TahfidzConsistencyController extends Controller
{
    public function __construct(
        private readonly TahfidzAccess $access,
        private readonly TahfidzRaporService $rapor,
        private readonly TahfidzTasmiService $tasmi,
    ) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $academicYear = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::orderByDesc('start_date')->first();
        $schoolId = $this->schoolId($request);
        $semester = $academicYear?->semester === 'genap' ? 'genap' : 'ganjil';
        $groupIds = $this->accessibleGroupIds($request, $academicYear?->id, $schoolId);

        $issues = collect();

        // 1) Leger: jumlah juz vs progres juz.
        $studentIds = TahfidzGroupMember::whereIn('tahfidz_group_id', $groupIds)
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->where('status', 'aktif')
            ->pluck('student_id')
            ->unique();

        $progressByStudent = TahfidzJuzProgress::whereIn('student_id', $studentIds)
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->get()
            ->groupBy('student_id');

        $recaps = TahfidzProgressRecap::with('student:id,name,nis')
            ->whereIn('student_id', $studentIds)
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->orderByDesc('semester')
            ->get()
            ->keyBy('student_id');

        foreach ($recaps as $studentId => $recap) {
            $actual = ($progressByStudent[$studentId] ?? collect())
                ->whereIn('status', ['selesai_ziyadah', 'sudah_tasmi'])
                ->count();

            if ($actual !== (int) $recap->total_juz_completed) {
                $issues->push([
                    'category' => 'Leger: jumlah juz',
                    'student' => $recap->student,
                    'context' => 'Rekap semester '.$recap->semester,
                    'expected' => $actual.' juz',
                    'stored' => (int) $recap->total_juz_completed.' juz',
                    'detail' => 'Jumlah juz di rekap tidak sama dengan progres juz.',
                    'student_id' => $studentId,
                ]);
            }
        }

        // 2) Rapor: nilai THF tersimpan vs perhitungan setoran/STS/SAS saat ini.
        if ($academicYear) {
            $books = TeacherAdminBook::with(['subject', 'studyGroup:id,name'])
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('academic_year_id', $academicYear->id)
                ->where('semester', $semester)
                ->where('is_active', true)
                ->get()
                ->filter(fn ($book) => $this->rapor->isTahfidzSubject($book->subject))
                ->take(20);

            foreach ($books as $book) {
                $histories = StudentClassHistory::where('study_group_id', $book->study_group_id)
                    ->where('academic_year_id', $academicYear->id)
                    ->get(['student_id']);

                $nilaiRows = NilaiSumatif::where('admin_book_id', $book->id)
                    ->whereIn('student_id', $histories->pluck('student_id'))
                    ->where('semester', $semester)
                    ->get()
                    ->keyBy('student_id');

                foreach ($histories as $history) {
                    $row = $nilaiRows[$history->student_id] ?? null;
                    $expected = $this->rapor->scoreFor(
                        $history->student_id, $book, $academicYear->id, $semester, 'sas', $row
                    );

                    if ($expected === null) {
                        continue;
                    }

                    $stored = AcademicNilai::resolveTampil('sas', $row);
                    if ($stored === null || abs($stored - $expected) > 0.01) {
                        $issues->push([
                            'category' => 'Rapor: nilai THF',
                            'student' => $row?->student ?? Student::find($history->student_id, ['id', 'name', 'nis']),
                            'context' => $book->studyGroup?->name.' · '.$semester,
                            'expected' => number_format($expected, 2),
                            'stored' => $stored !== null ? number_format($stored, 2) : 'belum tersimpan',
                            'detail' => 'Nilai THF tersimpan berbeda dari perhitungan saat ini.',
                            'student_id' => $history->student_id,
                        ]);
                    }
                }
            }
        }

        // 3) Syahadah: nilai/predikat/nama Arab/jenis vs data tasmi'.
        TahfidzCertificate::with(['student:id,name,nis,name_ar', 'participant.score', 'participant.session:id'])
            ->when(! $this->access->canViewAllSchools($request->user()), fn ($q) => $q->where('school_id', $schoolId))
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->get()
            ->each(function (TahfidzCertificate $certificate) use ($issues) {
                $score = $certificate->participant?->score;
                if (! $score) {
                    $issues->push([
                        'category' => 'Syahadah: nilai',
                        'student' => $certificate->student,
                        'context' => $certificate->certificate_number,
                        'expected' => 'nilai peserta tasmi’',
                        'stored' => 'tidak ada',
                        'detail' => 'Syahadah tidak memiliki nilai peserta.',
                        'student_id' => $certificate->student_id,
                    ]);

                    return;
                }

                if ($certificate->nilai_akhir !== null && abs((float) $certificate->nilai_akhir - (float) $score->nilai_akhir) > 0.01) {
                    $issues->push([
                        'category' => 'Syahadah: nilai',
                        'student' => $certificate->student,
                        'context' => $certificate->certificate_number,
                        'expected' => number_format((float) $score->nilai_akhir, 2),
                        'stored' => number_format((float) $certificate->nilai_akhir, 2),
                        'detail' => 'Nilai syahadah berbeda dari nilai peserta tasmi’.',
                        'student_id' => $certificate->student_id,
                    ]);
                }
                if (blank($certificate->student?->name_ar)) {
                    $issues->push([
                        'category' => 'Syahadah: nama Arab',
                        'student' => $certificate->student,
                        'context' => $certificate->certificate_number,
                        'expected' => 'nama Arab (A)',
                        'stored' => 'kosong',
                        'detail' => 'Nama Arab pada syahadah belum diisi.',
                        'student_id' => $certificate->student_id,
                    ]);
                }
                if ($certificate->total_juz_completed) {
                    $expectedType = $this->tasmi->certificateType((int) $certificate->total_juz_completed);
                    if ($certificate->certificate_type !== $expectedType) {
                        $issues->push([
                            'category' => 'Syahadah: jenis',
                            'student' => $certificate->student,
                            'context' => $certificate->certificate_number,
                            'expected' => $expectedType,
                            'stored' => $certificate->certificate_type,
                            'detail' => 'Jenis syahadah tidak sesuai jumlah juz.',
                            'student_id' => $certificate->student_id,
                        ]);
                    }
                }
            });

        $stats = [
            'total' => $issues->count(),
            'rapor' => $issues->where('category', 'Rapor: nilai THF')->count(),
            'leger' => $issues->where('category', 'Leger: jumlah juz')->count(),
            'syahadah' => $issues->filter(fn ($row) => str_starts_with($row['category'], 'Syahadah'))->count(),
        ];

        return view('tahfidz.consistency.index', compact('userId', 'issues', 'stats', 'academicYear'));
    }

    private function accessibleGroupIds(Request $request, ?string $academicYearId, ?string $schoolId)
    {
        $user = $request->user();
        $query = TahfidzGroup::query()
            ->where('is_active', true)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId));

        if (! $this->access->canViewAllSchools($user)) {
            if ($this->access->isSchoolCoordinator($user, $schoolId) || $this->access->isSchoolLeader($user)) {
                $query->where('school_id', $schoolId);
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('teacher_id', $user->id)
                        ->orWhere('coordinator_id', $user->id)
                        ->orWhereHas('teachers', fn ($teacher) => $teacher->where('user_id', $user->id)->where('is_active', true));
                });
            }
        }

        return $query->pluck('id');
    }

    private function authorizeView(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $this->access->canViewAllSchools($user)
            || $this->access->isSchoolCoordinator($user, $this->schoolId($request))
            || $this->access->isSchoolLeader($user)
            || $this->access->isTahfidzTeacher($user)
            || (function_exists('canPermission') && canPermission('tahfidz_view')),
            403,
            'Anda tidak berwenang melihat audit konsistensi Tahfidz.'
        );
    }

    private function schoolId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId') ?: $request->user()?->employment?->school_id;
    }
}
