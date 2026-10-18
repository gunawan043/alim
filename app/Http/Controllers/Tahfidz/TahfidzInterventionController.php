<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzIntervention;
use App\Services\TahfidzAccess;
use Illuminate\Http\Request;

class TahfidzInterventionController extends Controller
{
    public function __construct(private readonly TahfidzAccess $access) {}

    public function store(Request $request, string $userId, string $studentId)
    {
        $academicYear = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::orderByDesc('start_date')->first();
        $member = $this->memberFor($request, $studentId, $academicYear?->id);

        $validated = $request->validate([
            'type' => 'required|in:panggilan_wali,bimbingan_tambahan,target_ulang,motivasi,lainnya',
            'notes' => 'nullable|string|max:3000',
            'due_date' => 'nullable|date',
        ]);

        TahfidzIntervention::create([
            'student_id' => $studentId,
            'tahfidz_group_id' => $member->tahfidz_group_id,
            'academic_year_id' => $member->academic_year_id,
            'type' => $validated['type'],
            'title' => $this->title($validated['type']),
            'notes' => $validated['notes'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'status' => 'open',
            'assigned_to' => $request->user()?->id,
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Tindak lanjut berhasil dibuat.');
    }

    public function complete(Request $request, string $userId, string $interventionId)
    {
        $intervention = TahfidzIntervention::with('group')->findOrFail($interventionId);
        $this->authorizeGroup($request, $intervention->group);

        $intervention->update([
            'status' => 'selesai',
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Tindak lanjut ditandai selesai.');
    }

    private function memberFor(Request $request, string $studentId, ?string $academicYearId): TahfidzGroupMember
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId') ?: $user?->employment?->school_id;

        $query = TahfidzGroupMember::query()
            ->where('student_id', $studentId)
            ->where('status', 'aktif')
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId));

        if (! $this->access->canViewAllSchools($user)) {
            if ($this->access->isSchoolCoordinator($user, $schoolId) || $this->access->isSchoolLeader($user)) {
                $query->whereHas('group', fn ($group) => $group->where('school_id', $schoolId));
            } else {
                $query->whereHas('group', function ($group) use ($user) {
                    $group->where('teacher_id', $user->id)
                        ->orWhere('coordinator_id', $user->id)
                        ->orWhereHas('teachers', fn ($teacher) => $teacher->where('user_id', $user->id)->where('is_active', true));
                });
            }
        }

        return $query->firstOrFail();
    }

    private function authorizeGroup(Request $request, ?TahfidzGroup $group): void
    {
        abort_unless(
            $group && ($this->access->canViewGroup($request->user(), $group)
                || $this->access->canSubstituteSetoran($request->user(), $group)),
            403,
            'Anda tidak berwenang menindaklanjuti santri ini.'
        );
    }

    private function title(string $type): string
    {
        return match ($type) {
            'panggilan_wali' => 'Panggilan wali santri',
            'bimbingan_tambahan' => 'Bimbingan tahfizh tambahan',
            'target_ulang' => 'Penyesuaian target hafalan',
            'motivasi' => 'Pendampingan motivasi',
            default => 'Tindak lanjut tahfizh',
        };
    }
}
