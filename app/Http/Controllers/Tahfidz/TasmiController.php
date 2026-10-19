<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Student;
use App\Models\TahfidzCertificate;
use App\Models\TahfidzTasmianParticipant;
use App\Models\TahfidzTasmianScore;
use App\Models\TahfidzTasmianSession;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\TahfidzAccess;
use App\Services\TahfidzTasmiService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class TasmiController extends Controller
{
    public function __construct(
        private readonly TahfidzAccess $access,
        private readonly TahfidzTasmiService $tasmi,
    ) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $activeYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $academicYearId = $request->input('academic_year_id', $activeYear?->id);
        $schoolId = $this->schoolId($request);

        $query = TahfidzTasmianSession::with(['school:id,name', 'academicYear:id,name'])
            ->withCount(['participants', 'scores'])
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('school_id'), fn ($q) => $q->where('school_id', $request->school_id))
            ->orderByDesc('session_date');

        if (! $this->access->canViewAllSchools($request->user())) {
            if ($this->access->isSchoolCoordinator($request->user(), $schoolId)
                || $this->access->isSchoolLeader($request->user())
                || $this->access->isTahfidzTeacher($request->user())) {
                $query->where('school_id', $schoolId);
            } else {
                $query->where(function ($q) use ($request) {
                    $q->where('created_by', $request->user()->id)
                        ->orWhereHas('participants', fn ($p) => $p->where('mustami_id', $request->user()->id));
                });
            }
        }

        $sessions = $query->get();
        $schools = $this->access->canViewAllSchools($request->user())
            ? School::orderBy('name')->get(['id', 'name'])
            : School::whereKey($schoolId)->get(['id', 'name']);
        $canManage = $this->access->isSuperAdmin($request->user())
            || $this->access->isDepartment($request->user())
            || $this->access->isSchoolCoordinator($request->user(), $schoolId);
        $stats = [
            'total' => $sessions->count(),
            'scheduled' => $sessions->whereIn('status', ['terjadwal', 'berlangsung'])->count(),
            'participants' => $sessions->sum('participants_count'),
            'scored' => $sessions->sum('scores_count'),
        ];

        return view('tahfidz.tasmi.index', compact(
            'userId', 'sessions', 'academicYears', 'academicYearId', 'schools',
            'canManage', 'stats', 'schoolId'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $this->authorizeManage($request);
        $validated = $this->validateSession($request);
        $school = $validated['school_id'] ? School::find($validated['school_id']) : null;
        $workUnitId = $school?->work_unit_id
            ?: WorkUnit::where('code', TahfidzAccess::DEPARTMENT_CODE)->value('id');

        abort_if(! $workUnitId, 422, 'Satuan kerja Tahfidz belum tersedia.');

        $session = TahfidzTasmianSession::create([
            'work_unit_id' => $workUnitId,
            'school_id' => $school?->id,
            'academic_year_id' => $validated['academic_year_id'],
            'session_name' => $validated['session_name'],
            'session_date' => $validated['session_date'],
            'session_time_start' => $validated['session_time_start'] ?? null,
            'session_time_end' => $validated['session_time_end'] ?? null,
            'location' => $validated['location'] ?? null,
            'session_type' => $validated['session_type'],
            'status' => $validated['status'],
            'created_by' => $request->user()?->id,
        ]);

        return redirect()
            ->route('user.tahfidz.tasmi.show', ['userId' => $userId, 'id' => $session->id])
            ->with('success', 'Sesi tasmi’ berhasil dibuat.');
    }

    public function update(Request $request, string $userId, string $id)
    {
        $session = TahfidzTasmianSession::findOrFail($id);
        $this->authorizeManageSession($request, $session);
        $validated = $this->validateSession($request);
        $school = $validated['school_id'] ? School::find($validated['school_id']) : null;
        $workUnitId = $school?->work_unit_id
            ?: WorkUnit::where('code', TahfidzAccess::DEPARTMENT_CODE)->value('id');

        $session->update([
            'work_unit_id' => $workUnitId,
            'school_id' => $school?->id,
            'academic_year_id' => $validated['academic_year_id'],
            'session_name' => $validated['session_name'],
            'session_date' => $validated['session_date'],
            'session_time_start' => $validated['session_time_start'] ?? null,
            'session_time_end' => $validated['session_time_end'] ?? null,
            'location' => $validated['location'] ?? null,
            'session_type' => $validated['session_type'],
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Sesi tasmi’ diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $session = TahfidzTasmianSession::withCount(['participants', 'scores'])->findOrFail($id);
        $this->authorizeManageSession($request, $session);
        abort_if($session->participants_count > 0 || $session->scores_count > 0, 422, 'Sesi yang memiliki peserta/nilai tidak dapat dihapus.');
        $session->delete();

        return redirect()->route('user.tahfidz.tasmi.index', ['userId' => $userId])->with('success', 'Sesi tasmi’ dihapus.');
    }

    private function validateSession(Request $request): array
    {
        $validated = $request->validate([
            'school_id' => 'nullable|exists:schools,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'session_name' => 'required|string|max:191',
            'session_date' => 'required|date',
            'session_time_start' => 'nullable|date_format:H:i',
            'session_time_end' => 'nullable|date_format:H:i',
            'location' => 'nullable|string|max:191',
            'session_type' => 'required|in:reguler,kenaikan_juz,khatam,khusus',
            'status' => 'required|in:draft,terjadwal,berlangsung,selesai',
        ]);

        if (! $this->access->canViewAllSchools($request->user())) {
            $validated['school_id'] = $this->schoolId($request);
        }

        return $validated;
    }

    public function show(Request $request, string $userId, string $id)
    {
        $session = TahfidzTasmianSession::with([
            'school', 'academicYear',
            'participants' => fn ($q) => $q->with([
                'student.currentClassHistory.studyGroup', 'mustami:id,name', 'score', 'certificate',
            ])->orderBy('nomor_urut'),
        ])->withCount('participants')->findOrFail($id);
        $this->authorizeSession($request, $session);

        $participantStudentIds = $session->participants->pluck('student_id');
        $availableStudents = Student::query()
            ->where('status', 'active')
            ->when($session->school_id, fn ($q) => $q->where('school_id', $session->school_id))
            ->whereNotIn('id', $participantStudentIds)
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'nis', 'school_id']);

        $mustamis = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name']);

        $canManage = $this->canManageSession($request, $session);
        $canScore = $canManage || $session->participants->contains(fn ($participant) => (string) $participant->mustami_id === (string) $request->user()->id);

        return view('tahfidz.tasmi.show', compact(
            'userId', 'session', 'availableStudents', 'mustamis', 'canManage', 'canScore'
        ));
    }

    public function addParticipant(Request $request, string $userId, string $id)
    {
        $session = TahfidzTasmianSession::findOrFail($id);
        $this->authorizeManageSession($request, $session);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'juz_tasmi' => 'nullable|integer|min:1|max:30',
            'juz_detail' => 'nullable|string|max:500',
            'materi_detail' => 'nullable|string|max:191',
            'mustami_id' => 'nullable|exists:users,id',
            'waktu_mulai' => 'nullable|date_format:H:i',
            'waktu_selesai' => 'nullable|date_format:H:i',
            'tempat' => 'nullable|string|max:100',
        ]);

        $student = Student::where('status', 'active')
            ->when($session->school_id, fn ($q) => $q->where('school_id', $session->school_id))
            ->findOrFail($validated['student_id']);

        $juzDetail = collect(explode(',', (string) ($validated['juz_detail'] ?? '')))
            ->map(fn ($value) => (int) trim($value))
            ->filter(fn ($value) => $value >= 1 && $value <= 30)
            ->unique()
            ->values()
            ->all();

        if (empty($juzDetail) && ! empty($validated['juz_tasmi'])) {
            $juzDetail = [(int) $validated['juz_tasmi']];
        }

        $nomor = ((int) $session->participants()->max('nomor_urut')) + 1;

        TahfidzTasmianParticipant::firstOrCreate(
            [
                'tasmian_session_id' => $session->id,
                'student_id' => $student->id,
            ],
            [
                'academic_year_id' => $session->academic_year_id,
                'nomor_urut' => $nomor,
                'juz_tasmi' => $validated['juz_tasmi'] ?? ($juzDetail[0] ?? null),
                'juz_detail' => $juzDetail ?: null,
                'materi_detail' => $validated['materi_detail'] ?? null,
                'mustami_id' => $validated['mustami_id'] ?? null,
                'waktu_mulai' => $validated['waktu_mulai'] ?? null,
                'waktu_selesai' => $validated['waktu_selesai'] ?? null,
                'tempat' => $validated['tempat'] ?? $session->location,
                'status' => 'terdaftar',
                'requested_by' => $request->user()?->id,
                'requested_at' => now(),
                'verification_status' => 'pending',
            ]
        );

        return back()->with('success', 'Peserta tasmi’ ditambahkan.');
    }

    public function removeParticipant(Request $request, string $userId, string $id, string $participantId)
    {
        $session = TahfidzTasmianSession::findOrFail($id);
        $this->authorizeManageSession($request, $session);
        $participant = TahfidzTasmianParticipant::where('tasmian_session_id', $session->id)->findOrFail($participantId);
        abort_if($participant->score()->exists() || $participant->certificate()->exists(), 422, 'Peserta yang sudah dinilai tidak dapat dihapus.');
        $participant->delete();

        return back()->with('success', 'Peserta dikeluarkan dari sesi.');
    }

    public function score(Request $request, string $userId, string $participantId)
    {
        $participant = TahfidzTasmianParticipant::with('session')->findOrFail($participantId);
        $this->authorizeScore($request, $participant);
        abort_if($participant->verification_status === 'verified', 403, 'Nilai yang sudah diverifikasi koordinator tidak dapat diubah.');

        $validated = $request->validate([
            'tahfizh_score_raw' => 'required|numeric|min:0|max:100',
            'tajwid_score_raw' => 'required|numeric|min:0|max:100',
            'fashohah_score_raw' => 'required|numeric|min:0|max:100',
            'catatan_penilai' => 'nullable|string|max:3000',
        ]);

        $calculated = $this->tasmi->calculate(
            (float) $validated['tahfizh_score_raw'],
            (float) $validated['tajwid_score_raw'],
            (float) $validated['fashohah_score_raw'],
        );

        TahfidzTasmianScore::updateOrCreate(
            ['participant_id' => $participant->id],
            [
                'student_id' => $participant->student_id,
                'tasmian_session_id' => $participant->tasmian_session_id,
                'evaluator_id' => $request->user()->id,
                'tahfizh_score_raw' => $calculated['tahfizh'],
                'tahfizh_nilai' => $calculated['tahfizh'],
                'tajwid_score_raw' => $calculated['tajwid'],
                'tajwid_nilai' => $calculated['tajwid'],
                'fashohah_score_raw' => $calculated['fashohah'],
                'fashohah_nilai' => $calculated['fashohah'],
                'nilai_akhir' => $calculated['nilai_akhir'],
                'predikat' => $calculated['predikat'],
                'catatan_penilai' => $validated['catatan_penilai'] ?? null,
            ]
        );

        $participant->update([
            'status' => 'selesai',
            'verification_status' => 'pending',
            'verified_by' => null,
            'verified_at' => null,
        ]);
        $remaining = $participant->session->participants()
            ->whereNotIn('status', ['selesai', 'tidak_hadir'])
            ->exists();
        $participant->session->update(['status' => $remaining ? 'berlangsung' : 'selesai']);

        return back()->with('success', 'Nilai tasmi’ disimpan: '.number_format($calculated['nilai_akhir'], 2).' ('.$this->tasmi->predicateArabic($calculated['predikat']).').');
    }

    public function verifyParticipant(Request $request, string $userId, string $participantId)
    {
        $participant = TahfidzTasmianParticipant::with(['session', 'score'])->findOrFail($participantId);
        $this->authorizeManageSession($request, $participant->session);
        abort_unless($participant->score, 422, 'Nilai peserta belum diisi.');

        if ($participant->verification_status === 'verified') {
            return back()->with('success', 'Nilai peserta sudah terverifikasi.');
        }

        $participant->update([
            'verification_status' => 'verified',
            'verified_by' => $request->user()?->id,
            'verified_at' => now(),
            'verification_notes' => $request->input('verification_notes'),
        ]);

        return back()->with('success', 'Nilai tasmi’ peserta berhasil diverifikasi koordinator.');
    }

    public function instrument(Request $request, string $userId, string $participantId)
    {
        $participant = TahfidzTasmianParticipant::with([
            'student', 'session.school', 'session.academicYear', 'mustami', 'score.evaluator',
        ])->findOrFail($participantId);
        $this->authorizeSession($request, $participant->session);

        return view('tahfidz.tasmi.instrument', compact('participant'));
    }

    public function certificates(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $activeYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $academicYearId = $request->input('academic_year_id', $activeYear?->id);
        $schoolId = $this->schoolId($request);

        $query = TahfidzCertificate::with([
            'student:id,name,nis,name_ar', 'school:id,name,npsn',
            'academicYear:id,name', 'participant.session:id,session_name,session_date',
        ])
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->when($request->filled('certificate_type'), fn ($q) => $q->where('certificate_type', $request->certificate_type))
            ->when($request->filled('school_id'), fn ($q) => $q->where('school_id', $request->school_id))
            ->orderByDesc('issued_date')
            ->orderByDesc('created_at');

        if (! $this->access->canViewAllSchools($request->user())) {
            $query->where('school_id', $schoolId);
        }

        $certificates = $query->get();
        $schools = $this->access->canViewAllSchools($request->user())
            ? School::orderBy('name')->get(['id', 'name'])
            : School::whereKey($schoolId)->get(['id', 'name']);
        $stats = [
            'total' => $certificates->count(),
            'this_month' => $certificates->filter(fn ($certificate) => $certificate->issued_date?->isSameMonth(now()))->count(),
            'printed' => $certificates->where('is_printed', true)->count(),
        ];

        return view('tahfidz.tasmi.certificates', compact(
            'userId', 'certificates', 'academicYears', 'academicYearId', 'schools', 'stats'
        ));
    }

    public function certificateAudit(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $activeYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $academicYearId = $request->input('academic_year_id', $activeYear?->id);
        $schoolId = $this->schoolId($request);

        $certificates = TahfidzCertificate::with([
            'student:id,name,nis,name_ar', 'school:id,name,npsn', 'academicYear:id,name',
            'participant.score', 'participant.session:id,session_name',
        ])
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->when(! $this->access->canViewAllSchools($request->user()), fn ($q) => $q->where('school_id', $schoolId))
            ->orderByDesc('issued_date')
            ->get();

        $audit = $certificates->map(function (TahfidzCertificate $certificate) {
            $problems = [];

            if (! $certificate->student) {
                $problems[] = 'Data santri tidak ditemukan.';
            }
            if (blank($certificate->student?->name_ar)) {
                $problems[] = 'Nama Arab (A) belum diisi.';
            }
            if (blank($certificate->certificate_number)) {
                $problems[] = 'Nomor syahadah kosong.';
            }
            if ($certificate->nilai_akhir === null) {
                $problems[] = 'Nilai akhir kosong.';
            }
            if (blank($certificate->predikat)) {
                $problems[] = 'Predikat kosong.';
            }
            if (! $certificate->participant?->score) {
                $problems[] = 'Nilai peserta tasmi’ tidak ditemukan.';
            }

            $juzCount = count($certificate->juz_detail ?? []);
            if ($certificate->total_juz_completed && $juzCount > 0 && $juzCount !== (int) $certificate->total_juz_completed) {
                $problems[] = 'Jumlah juz tidak sama dengan daftar juz.';
            }

            if ($certificate->total_juz_completed) {
                $expectedType = $this->tasmi->certificateType((int) $certificate->total_juz_completed);
                if ($certificate->certificate_type !== $expectedType) {
                    $problems[] = 'Jenis syahadah tidak sesuai jumlah juz.';
                }
            }

            return ['certificate' => $certificate, 'problems' => $problems];
        })->filter(fn ($row) => ! empty($row['problems']))->values();

        $stats = [
            'total' => $certificates->count(),
            'complete' => $certificates->count() - $audit->count(),
            'issues' => $audit->count(),
        ];

        return view('tahfidz.tasmi.certificate-audit', compact(
            'userId', 'audit', 'academicYears', 'academicYearId', 'stats'
        ));
    }

    public function issueCertificate(Request $request, string $userId, string $participantId)
    {
        $participant = TahfidzTasmianParticipant::with(['session.school', 'session.academicYear', 'student', 'score'])->findOrFail($participantId);
        $this->authorizeManageSession($request, $participant->session);

        if ($participant->verification_status !== 'verified') {
            return back()->with('error', 'Nilai peserta harus diverifikasi koordinator sebelum syahadah diterbitkan.');
        }

        try {
            $certificate = $this->tasmi->issueCertificate($participant, $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('user.tahfidz.tasmi.certificate.print', ['userId' => $userId, 'certificateId' => $certificate->id])
            ->with('success', 'Syahadah berhasil diterbitkan.');
    }

    public function certificatePrint(Request $request, string $userId, string $certificateId)
    {
        $certificate = TahfidzCertificate::with([
            'student', 'school', 'academicYear', 'participant.session', 'issuer',
        ])->findOrFail($certificateId);
        $this->authorizeCertificate($request, $certificate);

        $certificate->update([
            'is_printed' => true,
            'printed_at' => now(),
        ]);

        return view('tahfidz.tasmi.certificate-print', compact('certificate'));
    }

    private function authorizeView(Request $request): void
    {
        $user = $request->user();
        $hasAssignment = TahfidzTasmianParticipant::where('mustami_id', $user->id)->exists()
            || TahfidzTasmianSession::where('created_by', $user->id)->exists();

        abort_unless(
            $this->access->canViewAllSchools($user)
            || $this->access->isSchoolCoordinator($user, $this->schoolId($request))
            || $this->access->isSchoolLeader($user)
            || $hasAssignment
            || $this->access->isTahfidzTeacher($user)
            || (function_exists('canPermission') && canPermission('tahfidz_view')),
            403,
            'Anda tidak berwenang melihat tasmi’ tahfidz.'
        );
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless(
            $this->access->isSuperAdmin($request->user())
            || $this->access->isDepartment($request->user())
            || $this->access->isSchoolCoordinator($request->user(), $this->schoolId($request)),
            403,
            'Anda tidak berwenang mengelola sesi tasmi’.'
        );
    }

    private function canManageSession(Request $request, TahfidzTasmianSession $session): bool
    {
        return $this->access->isSuperAdmin($request->user())
            || $this->access->isDepartment($request->user())
            || $this->access->isSchoolCoordinator($request->user(), $session->school_id, $session->academic_year_id);
    }

    private function authorizeManageSession(Request $request, TahfidzTasmianSession $session): void
    {
        abort_unless($this->canManageSession($request, $session), 403, 'Anda tidak berwenang mengelola sesi tasmi’ ini.');
    }

    private function authorizeSession(Request $request, TahfidzTasmianSession $session): void
    {
        if ($this->access->canViewAllSchools($request->user())) {
            return;
        }

        if ($this->access->isSchoolCoordinator($request->user(), $session->school_id, $session->academic_year_id)) {
            return;
        }

        if (($this->access->isSchoolLeader($request->user()) || $this->access->isTahfidzTeacher($request->user()))
            && (string) $session->school_id === (string) $this->schoolId($request)) {
            return;
        }

        if ((string) $session->created_by === (string) $request->user()->id) {
            return;
        }

        if ($session->participants()->where('mustami_id', $request->user()->id)->exists()) {
            return;
        }

        abort(403, 'Anda tidak berwenang melihat sesi tasmi’ ini.');
    }

    private function authorizeScore(Request $request, TahfidzTasmianParticipant $participant): void
    {
        $session = $participant->session;
        abort_unless(
            $this->canManageSession($request, $session)
            || (string) $participant->mustami_id === (string) $request->user()->id,
            403,
            'Hanya mustami’ atau pengelola sesi yang dapat menilai peserta ini.'
        );
    }

    private function authorizeCertificate(Request $request, TahfidzCertificate $certificate): void
    {
        if ($this->access->canViewAllSchools($request->user())) {
            return;
        }

        abort_unless(
            $this->access->isSchoolCoordinator($request->user(), $certificate->school_id, $certificate->academic_year_id)
            || (($this->access->isSchoolLeader($request->user()) || $this->access->isTahfidzTeacher($request->user())) && (string) $certificate->school_id === (string) $this->schoolId($request))
            || (string) $certificate->issued_by === (string) $request->user()->id
            || $certificate->participant?->session?->participants()
                ->where('mustami_id', $request->user()->id)->exists(),
            403,
            'Anda tidak berwenang melihat syahadah ini.'
        );
    }

    private function schoolId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId') ?: $request->user()?->employment?->school_id;
    }
}
