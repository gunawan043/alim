<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzJuzProgress;
use App\Models\TahfidzProgressRecap;
use App\Models\TahfidzSetoran;
use App\Models\TahfidzStudentTarget;
use App\Models\TahfidzSurahMaster;
use App\Services\TahfidzAccess;
use App\Services\TahfidzPresenceService;
use Illuminate\Http\Request;

class TahfidzInputController extends Controller
{
    public function __construct(private readonly TahfidzAccess $access) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $academicYear = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::orderByDesc('start_date')->first();
        $schoolId = $this->schoolId($request);
        $ownGroups = $this->accessibleGroups($request, $academicYear?->id);
        $schoolGroups = $this->schoolGroups($request, $academicYear?->id);
        $ownGroupIds = $ownGroups->pluck('id');
        $schoolGroupIds = $schoolGroups->pluck('id');
        $studentClassMap = TahfidzGroupMember::with('group.studyGroup')
            ->whereIn('tahfidz_group_id', $schoolGroupIds)
            ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->where('status', 'aktif')
            ->get()
            ->mapWithKeys(fn ($member) => [
                $member->student_id => $member->group?->studyGroup?->name ?? $member->group?->name ?? '—',
            ]);

        // Tabel utama hanya menampilkan santri di halaqah guru yang sedang login.
        $members = TahfidzGroupMember::with(['student:id,name,nis,school_id', 'group:id,name,school_id'])
            ->whereIn('tahfidz_group_id', $ownGroupIds)
            ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->where('status', 'aktif')
            ->get()
            ->sortBy(fn ($member) => $member->student?->name)
            ->values();

        // Dropdown pencarian mencakup seluruh santri aktif di satuan pendidikan.
        $schoolStudents = Student::with('currentClassHistory.studyGroup')
            ->where('status', 'active')
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->orderBy('name')
            ->limit(1000)
            ->get(['id', 'name', 'nis', 'school_id']);

        $surahs = TahfidzSurahMaster::query()->orderBy('number')->get(['number', 'name_latin', 'name_arabic']);

        $presenceToday = app(TahfidzPresenceService::class)
            ->forStudents($schoolStudents->pluck('id'), now());

        $selected = null;
        $selectedMember = null;
        $selectedGroup = null;
        $isSubstitute = false;
        $history = collect();
        $lastSetoran = null;
        $target = null;
        $progress = collect();
        $recap = null;
        $canInput = false;

        if ($request->filled('student_id')) {
            $selected = Student::find($request->input('student_id'));
            if (! $selected) {
                return redirect()
                    ->route('user.tahfidz.input.index', ['userId' => $userId])
                    ->with('error', 'Santri tidak ditemukan.');
            }

            $selectedMember = TahfidzGroupMember::with('group.school')
                ->whereIn('tahfidz_group_id', $schoolGroupIds)
                ->where('student_id', $selected->id)
                ->where('status', 'aktif')
                ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
                ->first();

            if (! $selectedMember) {
                return redirect()
                    ->route('user.tahfidz.input.index', ['userId' => $userId])
                    ->with('error', 'Santri belum terdaftar dalam halaqah pada satuan pendidikan ini.');
            }

            $selectedGroup = $selectedMember->group;
            $isSubstitute = ! $ownGroupIds->contains($selectedGroup->id);
            $canInput = $this->access->canInputSetoran($request->user(), $selectedGroup);

            $history = TahfidzSetoran::with(['teacher:id,name', 'verifier:id,name'])
                ->where('student_id', $selected->id)
                ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
                ->orderByDesc('setoran_date')
                ->orderByDesc('created_at')
                ->limit(30)
                ->get();
            $lastSetoran = $history->first();
            $target = TahfidzStudentTarget::where('student_id', $selected->id)
                ->where('tahfidz_group_id', $selectedGroup->id)
                ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
                ->orderByDesc('target_bulan')
                ->first();
            $progress = TahfidzJuzProgress::where('student_id', $selected->id)
                ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
                ->orderBy('juz_number')
                ->get();
            $recap = TahfidzProgressRecap::where('student_id', $selected->id)
                ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
                ->orderByDesc('semester')
                ->first();
        }

        return view('tahfidz.input.index', compact(
            'userId', 'academicYear', 'ownGroups', 'schoolGroups', 'members', 'schoolStudents', 'studentClassMap', 'surahs',
            'selected', 'selectedMember', 'selectedGroup', 'isSubstitute', 'history',
            'lastSetoran', 'target', 'progress', 'recap', 'canInput', 'presenceToday'
        ));
    }

    private function schoolGroups(Request $request, ?string $academicYearId)
    {
        $user = $request->user();
        $schoolId = $this->schoolId($request);

        return TahfidzGroup::query()
            ->with(['school:id,name', 'studyGroup:id,name'])
            ->where('is_active', true)
            ->when($academicYearId, fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when(! $this->access->canViewAllSchools($user) && ! $schoolId, fn ($query) => $query->whereIn('id', $this->accessibleGroups($request, $academicYearId)->pluck('id')))
            ->orderBy('name')
            ->get();
    }

    private function accessibleGroups(Request $request, ?string $academicYearId)
    {
        $user = $request->user();
        $schoolId = $this->schoolId($request);

        $query = TahfidzGroup::query()
            ->with('school:id,name')
            ->where('is_active', true)
            ->when($academicYearId, fn ($query) => $query->where('academic_year_id', $academicYearId));

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
        abort_unless(
            $this->access->canViewAllSchools($request->user())
            || $this->access->isSchoolCoordinator($request->user(), $this->schoolId($request))
            || $this->access->isSchoolLeader($request->user())
            || $this->access->isTahfidzTeacher($request->user())
            || (function_exists('canPermission') && canPermission('tahfidz_view')),
            403,
            'Anda tidak berwenang menginput hafalan tahfidz.'
        );
    }

    private function schoolId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId') ?: $request->user()?->employment?->school_id;
    }
}
