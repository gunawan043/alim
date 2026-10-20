<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzMutabaah;
use App\Services\TahfidzAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MutabaahController extends Controller
{
    private const SHOLAT_FIELDS = [
        'sholat_subuh', 'sholat_dzuhur', 'sholat_ashar', 'sholat_maghrib', 'sholat_isya',
    ];

    public function __construct(private readonly TahfidzAccess $access) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $groups = $this->accessibleGroups($request);
        $groupIds = $groups->pluck('id');
        $month = $this->month($request);
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $base = TahfidzMutabaah::query()
            ->whereIn('tahfidz_group_id', $groupIds)
            ->whereBetween('record_date', [$start->toDateString(), $end->toDateString()])
            ->when($request->filled('group_id'), fn ($q) => $q->where('tahfidz_group_id', $request->group_id))
            ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->student_id));

        $stats = [
            'total' => (clone $base)->count(),
            'jamaah' => (clone $base)
                ->where('sholat_subuh', 'berjamaah')
                ->where('sholat_dzuhur', 'berjamaah')
                ->where('sholat_ashar', 'berjamaah')
                ->where('sholat_maghrib', 'berjamaah')
                ->where('sholat_isya', 'berjamaah')
                ->count(),
            'tilawah' => (int) (clone $base)->sum('tilawah_halaman'),
            'tikror' => (int) (clone $base)->sum('tikror_mandiri_halaman'),
        ];

        $records = $base->with(['student:id,name,nis', 'group:id,name,school_id', 'recordedBy:id,name'])
            ->orderByDesc('record_date')
            ->orderBy('student_id')
            ->paginate(31)
            ->withQueryString();

        $inputGroups = $groups
            ->filter(fn ($group) => $this->access->canInputSetoran($request->user(), $group))
            ->values();

        $membersByGroup = TahfidzGroupMember::with('student:id,name,nis')
            ->whereIn('tahfidz_group_id', $inputGroups->pluck('id'))
            ->where('status', 'aktif')
            ->get()
            ->groupBy('tahfidz_group_id')
            ->map(fn ($members) => $members->map(fn ($member) => [
                'id' => $member->student?->id,
                'name' => $member->student?->name,
                'nis' => $member->student?->nis,
            ])->filter(fn ($student) => $student['id'])->values());

        $students = TahfidzGroupMember::with('student:id,name,nis')
            ->whereIn('tahfidz_group_id', $groupIds)
            ->where('status', 'aktif')
            ->get()
            ->pluck('student')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        return view('tahfidz.mutabaah.index', compact(
            'userId', 'groups', 'inputGroups', 'records', 'students', 'membersByGroup',
            'month', 'stats'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $validated = $this->validateMutabaah($request);
        $group = TahfidzGroup::findOrFail($validated['tahfidz_group_id']);
        $this->authorizeInput($request, $group);
        $this->assertMember($group, $validated['student_id']);

        $existing = TahfidzMutabaah::where('student_id', $validated['student_id'])
            ->whereDate('record_date', $validated['record_date'])
            ->first();

        $payload = array_merge($validated, [
            'tahfidz_group_id' => $group->id,
            'academic_year_id' => $group->academic_year_id,
            'recorded_by' => $request->user()?->id,
        ]);

        if ($existing) {
            $this->authorizeInput($request, $existing->group);
            $existing->update($payload);
        } else {
            TahfidzMutabaah::create($payload);
        }

        return back()->with('success', 'Mutaba’ah harian disimpan.');
    }

    public function update(Request $request, string $userId, string $id)
    {
        $record = TahfidzMutabaah::findOrFail($id);
        $this->authorizeInput($request, $record->group);
        $validated = $this->validateMutabaah($request);
        $group = TahfidzGroup::findOrFail($validated['tahfidz_group_id']);
        $this->authorizeInput($request, $group);
        $this->assertMember($group, $validated['student_id']);

        $record->update(array_merge($validated, [
            'tahfidz_group_id' => $group->id,
            'academic_year_id' => $group->academic_year_id,
            'recorded_by' => $request->user()?->id,
        ]));

        return back()->with('success', 'Mutaba’ah harian diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $record = TahfidzMutabaah::findOrFail($id);
        $this->authorizeInput($request, $record->group);
        $record->delete();

        return back()->with('success', 'Mutaba’ah harian dihapus.');
    }

    public function print(Request $request, string $userId, string $studentId)
    {
        $this->authorizeView($request);

        $student = Student::findOrFail($studentId);
        $month = $this->month($request);
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $groups = $this->accessibleGroups($request);

        $group = $request->filled('group_id')
            ? $groups->firstWhere('id', $request->input('group_id'))
            : $groups->first(fn ($candidate) => TahfidzGroupMember::query()
                ->where('tahfidz_group_id', $candidate->id)
                ->where('student_id', $student->id)
                ->where('status', 'aktif')
                ->exists());

        abort_unless($group, 403, 'Santri tidak berada dalam halaqah yang dapat Anda akses.');
        abort_unless(
            TahfidzGroupMember::where('tahfidz_group_id', $group->id)
                ->where('student_id', $student->id)
                ->exists(),
            403,
            'Santri bukan anggota halaqah ini.'
        );

        $records = TahfidzMutabaah::query()
            ->where('student_id', $student->id)
            ->whereBetween('record_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('record_date')
            ->get()
            ->keyBy(fn ($record) => (int) $record->record_date->day);

        $days = $month->daysInMonth;
        $monthLabel = $month->locale('id')->translatedFormat('F Y');

        return app(\App\Services\Pdf\DocumentPdfService::class)->fromView(
            'tahfidz.mutabaah.print',
            compact('student', 'group', 'month', 'monthLabel', 'days', 'records'),
            'Mutabaah-'.$student->name.'-'.$monthLabel,
            'landscape',
        );
    }

    private function validateMutabaah(Request $request): array
    {
        return $request->validate([
            'tahfidz_group_id' => 'required|exists:tahfidz_groups,id',
            'student_id' => 'required|exists:students,id',
            'record_date' => 'required|date',
            'sholat_subuh' => 'nullable|in:berjamaah,sendiri,qadha,tidak,uzur',
            'sholat_dzuhur' => 'nullable|in:berjamaah,sendiri,qadha,tidak,uzur',
            'sholat_ashar' => 'nullable|in:berjamaah,sendiri,qadha,tidak,uzur',
            'sholat_maghrib' => 'nullable|in:berjamaah,sendiri,qadha,tidak,uzur',
            'sholat_isya' => 'nullable|in:berjamaah,sendiri,qadha,tidak,uzur',
            'sholat_tahajud' => 'nullable|boolean',
            'sholat_dhuha' => 'nullable|boolean',
            'puasa_sunnah' => 'nullable|boolean',
            'sedekah' => 'nullable|boolean',
            'tilawah_halaman' => 'nullable|integer|min:0|max:604',
            'tikror_mandiri_halaman' => 'nullable|integer|min:0|max:604',
            'wirid_pagi' => 'nullable|boolean',
            'wirid_sore' => 'nullable|boolean',
            'catatan_musyrif' => 'nullable|string|max:3000',
        ]);
    }

    private function accessibleGroups(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->schoolId($request);
        $activeYear = AcademicYear::where('is_active', true)->first();

        $query = TahfidzGroup::query()
            ->with(['school:id,name', 'studyGroup:id,name'])
            ->where('is_active', true)
            ->when($activeYear, fn ($q) => $q->where('academic_year_id', $activeYear->id));

        if (! $this->access->canViewAllSchools($user)) {
            if ($this->access->isSchoolCoordinator($user, $schoolId)
                || $this->access->isSchoolLeader($user)) {
                $query->where('school_id', $schoolId);
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('teacher_id', $user->id)
                        ->orWhere('coordinator_id', $user->id)
                        ->orWhereHas('teachers', fn ($t) => $t->where('user_id', $user->id)->where('is_active', true)->where('can_input_setoran', true));
                });
            }
        }

        return $query->orderBy('name')->get();
    }

    private function assertMember(TahfidzGroup $group, string $studentId): void
    {
        abort_unless(
            TahfidzGroupMember::where('tahfidz_group_id', $group->id)
                ->where('student_id', $studentId)
                ->where('status', 'aktif')
                ->exists(),
            422,
            'Santri bukan anggota aktif halaqah ini.'
        );
    }

    private function authorizeInput(Request $request, ?TahfidzGroup $group): void
    {
        abort_unless($group && $this->access->canInputSetoran($request->user(), $group), 403, 'Anda tidak berwenang mencatat mutaba’ah halaqah ini.');
    }

    private function authorizeView(Request $request): void
    {
        $user = $request->user();
        $hasGroupAssignment = TahfidzGroup::query()
            ->where('is_active', true)
            ->where(function ($q) use ($user) {
                $q->where('teacher_id', $user->id)
                    ->orWhere('coordinator_id', $user->id)
                    ->orWhereHas('teachers', fn ($t) => $t->where('user_id', $user->id)->where('is_active', true));
            })
            ->exists();

        abort_unless(
            $this->access->canViewAllSchools($user)
            || $this->access->isSchoolCoordinator($user, $this->schoolId($request))
            || $this->access->isSchoolLeader($user)
            || $hasGroupAssignment
            || $this->access->isTahfidzTeacher($user)
            || (function_exists('canPermission') && canPermission('tahfidz_view')),
            403,
            'Anda tidak berwenang melihat mutaba’ah tahfidz.'
        );
    }

    private function month(Request $request): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m', (string) $request->input('month', now()->format('Y-m')))->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }

    private function schoolId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId') ?: $request->user()?->employment?->school_id;
    }
}
