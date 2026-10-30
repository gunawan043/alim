<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzSetoran;
use App\Models\TahfidzTasmianScore;
use App\Services\Pdf\DocumentPdfService;
use App\Services\TahfidzAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TahfidzGradebookController extends Controller
{
    public function __construct(private readonly TahfidzAccess $access) {}

    public function index(Request $request, string $userId)
    {
        $data = $this->gradebookData($request, $userId);

        return view('tahfidz.gradebook.index', $data);
    }

    public function print(Request $request, string $userId)
    {
        $data = $this->gradebookData($request, $userId);

        abort_unless($data['group'], 404, 'Pilih halaqah terlebih dahulu.');

        return app(DocumentPdfService::class)->fromView(
            'tahfidz.gradebook.print',
            $data,
            'Buku-Nilai-Tahfidz-'.$data['group']->name.'-'.$data['academicYear']?->name,
            'landscape',
        );
    }

    private function gradebookData(Request $request, string $userId): array
    {
        $this->authorizeView($request);

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $activeYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $groups = $this->accessibleGroups($request, $activeYear?->id);
        $groupId = $request->input('group_id', $groups->first()?->id);
        $group = $groups->firstWhere('id', $groupId);
        $semester = in_array($request->input('semester'), ['ganjil', 'genap'], true)
            ? $request->input('semester')
            : ($group?->academicYear?->semester ?: 'ganjil');

        $rows = collect();
        $stats = [
            'students' => 0,
            'setoran' => 0,
            'tikrar_avg' => null,
            'tasmi_avg' => null,
        ];

        if ($group) {
            $members = TahfidzGroupMember::with('student:id,name,nis')
                ->where('tahfidz_group_id', $group->id)
                ->where('status', 'aktif')
                ->get()
                ->sortBy(fn ($member) => $member->student?->name)
                ->values();
            $studentIds = $members->pluck('student_id');

            [$start, $end] = $this->semesterRange($activeYear, $semester);

            $setorans = TahfidzSetoran::query()
                ->whereIn('student_id', $studentIds)
                ->where('tahfidz_group_id', $group->id)
                ->whereBetween('setoran_date', [$start->toDateString(), $end->toDateString()])
                ->get()
                ->groupBy('student_id');

            $tasmiScores = TahfidzTasmianScore::query()
                ->whereIn('student_id', $studentIds)
                ->whereHas('session', fn ($query) => $query->where('academic_year_id', $group->academic_year_id))
                ->get()
                ->groupBy('student_id');

            $rows = $members->map(function ($member) use ($setorans, $tasmiScores) {
                $studentSetorans = $setorans->get($member->student_id, collect());
                $tasmi = $tasmiScores->get($member->student_id, collect());

                $tikrar = $studentSetorans->where('setoran_type', 'tikror');
                $lahnKhofi = $studentSetorans->sum(fn ($row) => (int) $row->lahn_khofi_count);
                $lahnJali = $studentSetorans->sum(fn ($row) => (int) $row->lahn_jali_count);

                return [
                    'student' => $member->student,
                    'ziyadah' => $studentSetorans->where('setoran_type', 'ziyadah')->count(),
                    'tikrar' => $tikrar->count(),
                    'murajaah' => $studentSetorans->where('setoran_type', 'murajaah')->count(),
                    'robet' => $studentSetorans->where('setoran_type', 'robet')->count(),
                    'tikrar_avg' => $tikrar->whereNotNull('nilai_tikrar')->avg('nilai_tikrar'),
                    'lahn_khofi' => $lahnKhofi,
                    'lahn_jali' => $lahnJali,
                    'lahn_avg' => $studentSetorans->whereNotNull('nilai_lahn')->avg('nilai_lahn'),
                    'setoran_avg' => $studentSetorans->whereNotNull('nilai_setoran')->avg('nilai_setoran'),
                    'tasmi_avg' => $tasmi->whereNotNull('nilai_akhir')->avg('nilai_akhir'),
                    'tasmi_count' => $tasmi->count(),
                ];
            })->values();

            $stats = [
                'students' => $rows->count(),
                'setoran' => $setorans->flatten(1)->count(),
                'tikrar_avg' => $rows->whereNotNull('tikrar_avg')->avg('tikrar_avg'),
                'tasmi_avg' => $rows->whereNotNull('tasmi_avg')->avg('tasmi_avg'),
            ];
        }

        return compact(
            'userId', 'academicYears', 'activeYear', 'groups', 'group',
            'groupId', 'semester', 'rows', 'stats'
        );
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function semesterRange(?AcademicYear $academicYear, string $semester): array
    {
        $start = $academicYear?->start_date
            ? Carbon::parse($academicYear->start_date)->startOfMonth()
            : now()->startOfYear();
        $end = $academicYear?->end_date
            ? Carbon::parse($academicYear->end_date)->endOfMonth()
            : now()->endOfYear();

        if ($semester === 'ganjil') {
            return [$start, $start->copy()->addMonths(5)->endOfMonth()->min($end)];
        }

        return [$start->copy()->addMonths(6)->startOfMonth(), $end];
    }

    private function accessibleGroups(Request $request, ?string $academicYearId): Collection
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId') ?: $user?->employment?->school_id;
        $query = TahfidzGroup::with(['school:id,name', 'studyGroup:id,name'])
            ->where('is_active', true)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId));

        if (! $this->access->canViewAllSchools($user)) {
            if ($this->access->isSchoolCoordinator($user, $schoolId)
                || $this->access->isSchoolLeader($user)) {
                $query->where('school_id', $schoolId);
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('teacher_id', $user->id)
                        ->orWhere('coordinator_id', $user->id)
                        ->orWhereHas('teachers', fn ($teacher) => $teacher->where('user_id', $user->id)->where('is_active', true));
                });
            }
        }

        return $query->orderBy('name')->get();
    }

    private function authorizeView(Request $request): void
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId') ?: $user?->employment?->school_id;

        abort_unless(
            $this->access->canViewAllSchools($user)
            || $this->access->isSchoolCoordinator($user, $schoolId)
            || $this->access->isSchoolLeader($user)
            || $this->access->isTahfidzTeacher($user)
            || (function_exists('canPermission') && canPermission('tahfidz_view')),
            403,
            'Anda tidak berwenang melihat buku nilai Tahfidz.'
        );
    }
}
