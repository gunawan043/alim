<?php

namespace App\Services;

use App\Models\QrClassToken;
use App\Models\StudyGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class QrTokenService
{
    /**
     * Find or create an active QR token for a study group.
     */
    public function findOrCreate(StudyGroup $studyGroup, ?string $academicYearId = null): QrClassToken
    {
        $token = QrClassToken::forStudyGroup($studyGroup->id)
            ->where('academic_year_id', $academicYearId)
            ->first();

        if (! $token) {
            $token = new QrClassToken([
                'study_group_id' => $studyGroup->id,
                'school_id' => $studyGroup->school_id,
                'academic_year_id' => $academicYearId,
            ]);
            $token->save();
        }

        return $token;
    }

    /**
     * Generate a signed URL for the QR code.
     * URL menuju endpoint proses scan (userId milik user yang login saat QR dibuat).
     */
    public function generateSignedUrl(QrClassToken $token): string
    {
        return URL::temporarySignedRoute(
            'user.teacher-qr.scan.process',
            now()->addHours(24),
            [
                'userId' => (string) (auth()->id() ?? $token->studyGroup?->homeroom_teacher_id ?? 'self'),
                'study_group_id' => $token->study_group_id,
            ]
        );
    }

    /**
     * Build the QR payload as JSON (same pattern as DormitoryPermit).
     */
    public function buildQrPayload(QrClassToken $token): array
    {
        $signedUrl = $this->generateSignedUrl($token);
        $studyGroup = $token->studyGroup()->first();

        return [
            'url' => $signedUrl,
            'study_group_id' => $token->study_group_id,
            'token_id' => $token->id,
            'token_hash' => $token->token_hash,
            'expires_at' => $token->qr_url_expires_at?->toISOString() ?? null,
            'class_name' => $studyGroup?->name ?? '',
            'class_code' => $studyGroup?->code ?? '',
        ];
    }

    /**
     * Verify that a request has a valid signature and belongs to this token.
     */
    public function verifyRequest(Request $request, string $studyGroupId): bool
    {
        if (! $request->hasValidSignature()) {
            return false;
        }

        $token = QrClassToken::where('study_group_id', $studyGroupId)
            ->where(function ($q) {
                $q->whereNull('qr_url_expires_at')
                    ->orWhere('qr_url_expires_at', '>', now());
            })
            ->first();

        return $token !== null;
    }
}
