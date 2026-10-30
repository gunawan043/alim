<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\MumtazMurtafiCandidate;
use App\Models\StudentAchievement;
use App\Services\Academic\MumtazMurtafiCandidateService;
use Illuminate\Http\Request;

class MumtazMurtafiCandidateController extends Controller
{
    public function index(Request $request, string $userId)
    {
        abort_unless(auth()->check() && canAccessUser($userId), 403, 'Akses ditolak.');

        $schoolId = $request->attributes->get('schoolContextId');
        $status = $request->get('status', MumtazMurtafiCandidate::STATUS_PENDING);
        if (! array_key_exists($status, MumtazMurtafiCandidate::statuses())) {
            $status = MumtazMurtafiCandidate::STATUS_PENDING;
        }

        $base = MumtazMurtafiCandidate::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        $counts = (clone $base)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $candidates = (clone $base)
            ->with(['student:id,name,nisn,nis', 'studyGroup.gradeLevel', 'academicYear', 'achievement:id,event_name'])
            ->where('status', $status)
            ->orderByDesc('average_score')
            ->orderBy('class_rank')
            ->paginate(20)
            ->withQueryString();

        return view('student-achievement.candidates.index', compact(
            'candidates', 'counts', 'status', 'userId', 'schoolId'
        ));
    }

    public function sync(Request $request, string $userId)
    {
        abort_unless(auth()->check() && canAccessUser($userId), 403, 'Akses ditolak.');

        $schoolId = $request->attributes->get('schoolContextId');
        $academicYearId = AcademicYear::where('is_active', true)->value('id');

        $result = app(MumtazMurtafiCandidateService::class)->syncPublishedRapors(
            $schoolId,
            $academicYearId,
            $userId,
        );

        $message = "Sinkronisasi rapor selesai: {$result['candidates']} kandidat dari {$result['groups']} kelompok rapor.";
        if ($result['failed'] > 0) {
            $message .= " {$result['failed']} kelompok gagal diproses; periksa log.";
        }

        return redirect()
            ->route('user.student-achievement.candidates.index', ['userId' => $userId])
            ->with('success', $message);
    }

    public function show(Request $request, string $userId, string $uuid)
    {
        abort_unless(auth()->check() && canAccessUser($userId), 403, 'Akses ditolak.');

        $candidate = $this->findCandidate($request, $uuid);

        return view('student-achievement.candidates.show', compact('candidate', 'userId'));
    }

    public function post(Request $request, string $userId, string $uuid)
    {
        abort_unless(auth()->check() && canAccessUser($userId), 403, 'Akses ditolak.');

        $candidate = $this->findCandidate($request, $uuid, true);

        $validated = $request->validate([
            'event_name' => 'required|string|max:191',
            'event_date' => 'required|date',
            'organizer' => 'nullable|string|max:191',
            'notes' => 'nullable|string|max:500',
            'certificate' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'notify_wali' => 'nullable|boolean',
            'notify_dormitory' => 'nullable|boolean',
        ]);

        $certificatePath = null;
        if ($request->hasFile('certificate')) {
            $certificatePath = $request->file('certificate')->store(
                'student-achievements/certificates/'.date('Y/m'),
                'public'
            );
        }

        $achievement = new StudentAchievement([
            'student_id' => $candidate->student_id,
            'school_id' => $candidate->school_id,
            'academic_year_id' => $candidate->academic_year_id,
            'achievement_type' => 'akademik',
            'event_name' => $validated['event_name'],
            'organizer' => $validated['organizer'] ?? $candidate->school?->name,
            'level' => 'internal',
            'position' => 'mumtaz_murtafi',
            'position_detail' => null,
            'event_date' => $validated['event_date'],
            'event_location' => $candidate->studyGroup?->full_name,
            'certificate_path' => $certificatePath,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $userId,
        ]);
        $achievement->notifyGuardianOnAchievement = $request->boolean('notify_wali', true);
        $achievement->notifyDormitoryReward = $request->boolean('notify_dormitory', true);
        $achievement->save();

        $candidate->update([
            'status' => MumtazMurtafiCandidate::STATUS_POSTED,
            'student_achievement_id' => $achievement->id,
            'posted_by' => $userId,
            'posted_at' => now(),
        ]);

        return redirect()
            ->route('user.student-achievement.candidates.index', ['userId' => $userId])
            ->with('success', 'Kandidat Mumtaz Murtafi\' berhasil diposting sebagai Prestasi Santri.');
    }

    private function findCandidate(Request $request, string $uuid, bool $onlyPending = false): MumtazMurtafiCandidate
    {
        $schoolId = $request->attributes->get('schoolContextId');

        return MumtazMurtafiCandidate::with(['student', 'school', 'studyGroup.gradeLevel', 'academicYear', 'achievement'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($onlyPending, fn ($q) => $q->where('status', MumtazMurtafiCandidate::STATUS_PENDING))
            ->findOrFail($uuid);
    }
}
