<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\QrClassToken;
use App\Models\StudyGroup;
use App\Services\QrTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClassQrController extends Controller
{
    protected QrTokenService $qrTokenService;

    public function __construct(QrTokenService $qrTokenService)
    {
        $this->qrTokenService = $qrTokenService;
    }

    /**
     * Generate and display QR code image for a class.
     * GET /qr/{studyGroupId}/image
     */
    public function qrImage(Request $request, string $study_group_id)
    {
        $studyGroup = StudyGroup::where('id', $study_group_id)
            ->where('is_active', true)
            ->with('school')
            ->firstOrFail();

        $academicYear = AcademicYear::where('is_active', true)->first();
        $token = $this->qrTokenService->findOrCreate($studyGroup, $academicYear?->id);
        $payload = $this->qrTokenService->buildQrPayload($token);

        $qrImage = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
            ->size(320)
            ->margin(2)
            ->generate(json_encode($payload));

        return response($qrImage, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Show the QR code page (with print option).
     * GET /qr/{studyGroupId}
     */
    public function show(Request $request, string $study_group_id)
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
    public function regenerate(Request $request, string $study_group_id)
    {
        $studyGroup = StudyGroup::where('id', $study_group_id)
            ->where('is_active', true)
            ->firstOrFail();

        $academicYear = AcademicYear::where('is_active', true)->first();

        // Find or create and regenerate
        $token = QrClassToken::where('study_group_id', $study_group_id)
            ->where('academic_year_id', $academicYear?->id)
            ->first();

        if ($token) {
            $token->regenerate();
        } else {
            $token = $this->qrTokenService->findOrCreate($studyGroup, $academicYear?->id);
        }

        return back()->with('success', 'QR baru berhasil dibuat.');
    }

    /**
     * Show QR print page (authenticated GTK/Waka view).
     * GET /qr/{studyGroupId}/print
     */
    public function print(Request $request, string $study_group_id)
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
     * Download QR image for printing.
     * GET /qr/{studyGroupId}/download
     */
    public function download(Request $request, string $study_group_id)
    {
        $imageResponse = $this->qrImage($request, $study_group_id);
        $filename = 'qr-kelas-' . $study_group_id . '.png';
        return $imageResponse;
    }
}
