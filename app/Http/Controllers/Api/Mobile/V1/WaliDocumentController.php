<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\RaportRegistration;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\TahfidzCertificate;
use App\Models\WaliSantri;
use App\Services\Arsip\RaporPdfService;
use App\Services\Pdf\DocumentPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dokumen unduhan wali:
 * - Rapor (PDF) yang sudah dipublikasikan
 * - Piagam prestasi (file yang diunggah)
 * - Syahadah tasmi (PDF)
 *
 * Semua endpoint mewajibkan link wali–santri aktif.
 */
class WaliDocumentController extends Controller
{
    public function raport(Request $request, string $userId, string $student): Response|JsonResponse
    {
        $link = $this->activeLink($request->user()->id, $student);

        if (! $link) {
            return $this->notFound('Santri tidak ditemukan atau bukan anak Anda.');
        }

        $registration = RaportRegistration::with(['student', 'studyGroup.gradeLevel', 'academicYear'])
            ->where('student_id', $student)
            ->where('is_published', true)
            ->latest('created_at')
            ->first();

        if (! $registration || ! $registration->studyGroup || ! $registration->academicYear) {
            return $this->notFound('Rapor belum dipublikasikan untuk santri ini.');
        }

        $studentModel = $registration->student ?? Student::find($student);
        $studyGroup = $registration->studyGroup;

        if (! $studentModel || ! $studyGroup) {
            return $this->notFound('Data rapor tidak lengkap.');
        }

        $jenis = $request->input('jenis') === 'sts' ? 'sts' : 'sas';
        $bahasa = $request->input('bahasa') === 'ar' ? 'ar' : 'id';

        $pdf = app(RaporPdfService::class)->renderSingle(
            $studentModel->school_id,
            $studyGroup,
            $registration->academicYear,
            (string) $registration->academic_year_id,
            (string) $registration->semester,
            $jenis,
            $studentModel,
            $bahasa,
        );

        $filename = 'Rapor-'.preg_replace('/[^A-Za-z0-9]+/', '-', $studentModel->name)
            .'-'.$registration->semester.'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function achievementCertificate(Request $request, string $userId, string $id): Response|JsonResponse
    {
        $achievement = StudentAchievement::find($id);

        if (! $achievement || ! $this->activeLink($request->user()->id, $achievement->student_id)) {
            return $this->notFound('Piagam tidak ditemukan atau bukan milik anak Anda.');
        }

        $path = (string) $achievement->certificate_path;

        if ($path === '') {
            return $this->notFound('File piagam tidak tersedia.');
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return redirect()->away($path);
        }

        if (! Storage::disk('local')->exists($path) && ! Storage::disk('public')->exists($path)) {
            return $this->notFound('File piagam tidak ditemukan.');
        }

        $filename = 'Piagam-'.preg_replace('/[^A-Za-z0-9]+/', '-', $achievement->event_name ?: 'Prestasi')
            .'.'.pathinfo($path, PATHINFO_EXTENSION);

        $disk = Storage::disk('local')->exists($path) ? 'local' : 'public';

        return Storage::disk($disk)->download($path, $filename);
    }

    public function tahfidzCertificates(Request $request, string $userId, string $student): JsonResponse
    {
        if (! $this->activeLink($request->user()->id, $student)) {
            return $this->notFound('Santri tidak ditemukan atau bukan anak Anda.');
        }

        $certificates = TahfidzCertificate::where('student_id', $student)
            ->latest('issued_date')
            ->get()
            ->map(fn (TahfidzCertificate $certificate) => [
                'id' => $certificate->id,
                'certificate_number' => $certificate->certificate_number,
                'type' => $certificate->certificate_type,
                'type_label' => $certificate->certificate_type_label,
                'issued_date' => $certificate->issued_date?->toDateString(),
                'total_juz_completed' => $certificate->total_juz_completed,
                'nilai_akhir' => $certificate->nilai_akhir,
                'predikat' => $certificate->predikat_label,
            ]);

        return response()->json([
            'success' => true,
            'data' => ['certificates' => $certificates],
        ]);
    }

    public function tahfidzCertificate(Request $request, string $userId, string $certificateId): Response|JsonResponse
    {
        $certificate = TahfidzCertificate::with([
            'student', 'school', 'academicYear', 'participant.session', 'issuer',
        ])->find($certificateId);

        if (! $certificate || ! $this->activeLink($request->user()->id, $certificate->student_id)) {
            return $this->notFound('Syahadah tidak ditemukan atau bukan milik anak Anda.');
        }

        $certificate->update([
            'is_printed' => true,
            'printed_at' => now(),
        ]);

        return app(DocumentPdfService::class)->fromView(
            'tahfidz.tasmi.certificate-print',
            compact('certificate'),
            'Syahadah-Tasmi-'.$certificate->student?->name,
            'landscape',
        );
    }

    private function activeLink(string $userId, ?string $studentId): ?WaliSantri
    {
        if (! $studentId) {
            return null;
        }

        return WaliSantri::where('user_id', $userId)
            ->where('student_id', $studentId)
            ->active()
            ->first();
    }

    private function notFound(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => ['code' => 'DOCUMENT_NOT_FOUND', 'message' => $message],
        ], 404);
    }
}
