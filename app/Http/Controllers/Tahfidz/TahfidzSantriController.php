<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\TahfidzCertificate;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzIntervention;
use App\Models\TahfidzJuzProgress;
use App\Models\TahfidzMutabaah;
use App\Models\TahfidzProgressRecap;
use App\Models\TahfidzSetoran;
use App\Models\TahfidzStudentTarget;
use App\Models\TahfidzTasmianParticipant;
use App\Services\TahfidzAccess;
use App\Services\TahfidzStudentAnalysisService;
use Illuminate\Http\Request;

class TahfidzSantriController extends Controller
{
    public function __construct(
        private readonly TahfidzAccess $access,
        private readonly TahfidzStudentAnalysisService $analysis,
    ) {}

    public function show(Request $request, string $userId, string $studentId)
    {
        $student = Student::with('school:id,name', 'currentClassHistory.studyGroup')->findOrFail($studentId);
        $academicYear = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::orderByDesc('start_date')->first();

        $member = TahfidzGroupMember::with('group.school', 'group.studyGroup', 'group.teacher')
            ->where('student_id', $student->id)
            ->where('status', 'aktif')
            ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->first();

        abort_unless($member && $this->canViewStudent($request, $member), 403, 'Anda tidak berwenang melihat detail Tahfidz santri ini.');

        $history = TahfidzSetoran::with(['teacher:id,name', 'verifier:id,name'])
            ->where('student_id', $student->id)
            ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->orderByDesc('setoran_date')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
        $progress = TahfidzJuzProgress::where('student_id', $student->id)
            ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->orderBy('juz_number')
            ->get();
        $recap = TahfidzProgressRecap::where('student_id', $student->id)
            ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->orderByDesc('semester')
            ->first();
        $targets = TahfidzStudentTarget::with('student:id,name')
            ->where('student_id', $student->id)
            ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->orderBy('target_bulan')
            ->get();
        $mutabaah = TahfidzMutabaah::where('student_id', $student->id)
            ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->orderByDesc('record_date')
            ->limit(10)
            ->get();
        $tasmi = TahfidzTasmianParticipant::with(['session:id,session_name,session_date,school_id', 'score', 'certificate'])
            ->where('student_id', $student->id)
            ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->latest()
            ->limit(10)
            ->get();
        $certificates = TahfidzCertificate::with('school:id,name')
            ->where('student_id', $student->id)
            ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->orderByDesc('issued_date')
            ->get();

        $analysis = $this->analysis->analyze($student->id, $academicYear?->id);
        $interventions = TahfidzIntervention::with(['assignedTo:id,name', 'creator:id,name'])
            ->where('student_id', $student->id)
            ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'setoran' => $history->count(),
            'verified' => $history->where('verification_status', 'verified')->count(),
            'juz' => $progress->whereIn('status', ['selesai_ziyadah', 'sudah_tasmi'])->count(),
            'average' => $history->where('verification_status', 'verified')->whereNotNull('nilai_setoran')->avg('nilai_setoran'),
            'target' => $recap?->pencapaian_target_persen,
            'mutabaah' => $mutabaah->count(),
            'certificates' => $certificates->count(),
        ];

        return view('tahfidz.santri.show', compact(
            'userId', 'student', 'member', 'academicYear', 'history', 'progress',
            'recap', 'targets', 'mutabaah', 'tasmi', 'certificates', 'stats', 'analysis', 'interventions'
        ));
    }

    private function canViewStudent(Request $request, TahfidzGroupMember $member): bool
    {
        $user = $request->user();
        $group = $member->group;
        $schoolId = $request->attributes->get('schoolContextId') ?: $user?->employment?->school_id;

        if ($this->access->canViewAllSchools($user)) {
            return true;
        }

        if ($this->access->isSchoolCoordinator($user, $group?->school_id, $member->academic_year_id)
            || $this->access->isSchoolLeader($user)) {
            return (string) $group?->school_id === (string) $schoolId;
        }

        if ($this->access->canViewGroup($user, $group)) {
            return true;
        }

        // Guru Tahfidz satu sekolah dapat melihat santri halaqah lain saat menjadi pengganti.
        return $this->access->canSubstituteSetoran($user, $group);
    }
}
