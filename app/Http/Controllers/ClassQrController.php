<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\QrClassToken;
use App\Models\StudyGroup;
use App\Services\QrTokenService;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ClassQrController extends Controller
{
    protected QrTokenService $qrTokenService;

    public function __construct(QrTokenService $qrTokenService)
    {
        $this->qrTokenService = $qrTokenService;
    }

    /**
     * Daftar QR per kelas (satu QR untuk satu kelas).
     * GET /{userId}/qr
     */
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $activeAy = AcademicYear::where('is_active', true)->first();

        $allStudyGroups = StudyGroup::with(['gradeLevel', 'homeroomTeacher:id,name'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('grade_level_id')
            ->orderBy('name')
            ->get();

        $tokens = QrClassToken::whereIn('study_group_id', $allStudyGroups->pluck('id'))
            ->when($activeAy, fn ($q) => $q->where('academic_year_id', $activeAy->id))
            ->get()
            ->sortByDesc('last_regenerated_at')
            ->keyBy('study_group_id');

        $qrStatus = $request->input('qr_status');
        if ($qrStatus === 'aktif') {
            $studyGroups = $allStudyGroups->filter(fn ($sg) => $tokens->has($sg->id))->values();
        } elseif ($qrStatus === 'belum') {
            $studyGroups = $allStudyGroups->filter(fn ($sg) => ! $tokens->has($sg->id))->values();
        } else {
            $studyGroups = $allStudyGroups;
        }

        $statistics = [
            'total' => $allStudyGroups->count(),
            'aktif' => $tokens->count(),
            'belum' => max(0, $allStudyGroups->count() - $tokens->count()),
            'wali_kelas' => $allStudyGroups->whereNotNull('homeroom_teacher_id')->count(),
        ];

        return view('teacher.qr.index', compact('studyGroups', 'tokens', 'activeAy', 'userId', 'statistics'));
    }

    /**
     * Generate and display QR code image for a class.
     * GET /qr/{studyGroupId}/image
     */
    public function qrImage(Request $request, string $userId, string $study_group_id)
    {
        $studyGroup = StudyGroup::where('id', $study_group_id)
            ->where('is_active', true)
            ->with('school')
            ->firstOrFail();

        $academicYear = AcademicYear::where('is_active', true)->first();
        $token = $this->qrTokenService->findOrCreate($studyGroup, $academicYear?->id);
        $payload = $this->qrTokenService->buildQrPayload($token);

        // SVG dipakai agar tidak bergantung pada ekstensi imagick.
        $qrImage = QrCode::format('svg')
            ->size(320)
            ->margin(2)
            ->generate(json_encode($payload));

        return response($qrImage, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Show the QR code page (with print option).
     * GET /qr/{studyGroupId}
     */
    public function show(Request $request, string $userId, string $study_group_id)
    {
        $studyGroup = StudyGroup::where('id', $study_group_id)
            ->where('is_active', true)
            ->with(['school', 'gradeLevel', 'homeroomTeacher'])
            ->firstOrFail();

        $academicYear = AcademicYear::where('is_active', true)->first();
        $token = $this->qrTokenService->findOrCreate($studyGroup, $academicYear?->id);
        $signedUrl = $this->qrTokenService->generateSignedUrl($token);

        return view('teacher.qr.print', compact('studyGroup', 'token', 'signedUrl'));
    }

    /**
     * Regenerate QR token for a study group.
     * POST /qr/{studyGroupId}/regenerate
     */
    public function regenerate(Request $request, string $userId, string $study_group_id)
    {
        $studyGroup = StudyGroup::where('id', $study_group_id)
            ->where('is_active', true)
            ->firstOrFail();

        $academicYear = AcademicYear::where('is_active', true)->first();

        $token = $this->qrTokenService->findOrCreate($studyGroup, $academicYear?->id);
        $token->regenerate();

        return back()->with('success', 'QR baru untuk '.($studyGroup->full_name ?? $studyGroup->name).' berhasil dibuat. QR lama tidak berlaku lagi.');
    }

    /**
     * Show QR print page (authenticated GTK/Waka view).
     * GET /qr/{studyGroupId}/print
     */
    public function print(Request $request, string $userId, string $study_group_id)
    {
        $studyGroup = StudyGroup::where('id', $study_group_id)
            ->where('is_active', true)
            ->with(['school', 'gradeLevel', 'homeroomTeacher'])
            ->firstOrFail();

        $academicYear = AcademicYear::where('is_active', true)->first();
        $token = $this->qrTokenService->findOrCreate($studyGroup, $academicYear?->id);
        $signedUrl = $this->qrTokenService->generateSignedUrl($token);

        return view('teacher.qr.print', compact('studyGroup', 'token', 'signedUrl'));
    }
}
