<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\LjkExam;
use App\Models\LjkParticipant;
use App\Models\LjkScan;
use App\Models\LjkScanAnswer;
use App\Services\Ljk\LjkAccess;
use App\Services\Ljk\LjkGradingService;
use App\Services\Ljk\LjkReviewService;
use App\Services\Ljk\LjkScanService;
use App\Services\Ljk\LjkSheetService;
use App\Services\Ljk\LjkSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * LJK Smart Scanner — API mobile guru (Sanctum).
 *
 * Otorisasi tetap di server (penugasan guru/kurikulum), bukan dari klien.
 * Kunci jawaban tidak pernah dikirim; hasil koreksi ber-audit.
 */
class LjkController extends Controller
{
    public function __construct(
        private readonly LjkScanService $scans,
        private readonly LjkSheetService $sheets,
        private readonly LjkGradingService $grading,
        private readonly LjkReviewService $review,
        private readonly LjkSyncService $sync,
        private readonly LjkAccess $access,
    ) {}

    private function success(array $data, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'request_id' => request()->header('X-Request-ID') ?: (string) Str::uuid(),
            'data' => $data,
        ], $status);
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'request_id' => request()->header('X-Request-ID') ?: (string) Str::uuid(),
            'error' => ['code' => $code, 'message' => $message],
        ], $status);
    }

    /**
     * GET /api/mobile/v1/ljk/exams — ujian yang dapat dipindai guru ini.
     */
    public function exams(Request $request): JsonResponse
    {
        $user = $request->user();
        $exams = LjkExam::query()
            ->with(['studyGroup:id,name', 'subject:id,name'])
            ->whereIn('status', [LjkExam::STATUS_AKTIF, LjkExam::STATUS_PENGUMPULAN, LjkExam::STATUS_REVIEW])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->filter(fn (LjkExam $exam) => $this->access->canScan($user, $exam))
            ->values()
            ->map(fn (LjkExam $exam) => [
                'id' => $exam->id,
                'code' => $exam->code,
                'judul' => $exam->judul,
                'jenis_ujian' => $exam->jenis_ujian,
                'status' => $exam->status,
                'status_label' => $exam->statusLabel(),
                'study_group' => $exam->studyGroup?->name,
                'subject' => $exam->subject?->name,
                'jumlah_soal' => $exam->jumlah_soal,
                'participants_count' => $exam->participants()->count(),
            ]);

        return $this->success(['exams' => $exams]);
    }

    /**
     * GET /api/mobile/v1/ljk/exams/{exam} — peserta & lembar.
     */
    public function examDetail(Request $request, string $exam): JsonResponse
    {
        $model = LjkExam::with(['studyGroup:id,name', 'subject:id,name'])->find($exam);
        if (! $model) {
            return $this->error('EXAM_NOT_FOUND', 'Ujian LJK tidak ditemukan.', 404);
        }

        if (! $this->access->canView($request->user(), $model)) {
            return $this->error('FORBIDDEN', 'Anda tidak berwenang melihat ujian ini.', 403);
        }

        $participants = LjkParticipant::query()
            ->where('ljk_exam_id', $model->id)
            ->with(['student:id,name,nis', 'sheet:id,participant_id,sheet_code,status'])
            ->orderBy('nomor_peserta')
            ->get()
            ->map(function (LjkParticipant $participant) {
                $scan = $this->grading->latestScan($participant);

                return [
                    'id' => $participant->id,
                    'nomor_peserta' => $participant->nomor_peserta,
                    'name' => $participant->student?->name,
                    'nis' => $participant->student?->nis,
                    'sheet_code' => $participant->sheet?->sheet_code,
                    'status' => $participant->status,
                    'nilai_akhir' => $participant->nilai_akhir !== null ? (float) $participant->nilai_akhir : null,
                    'last_scan_id' => $scan?->id,
                    'needs_review' => $scan
                        ? $scan->answers()->where('needs_review', true)->exists()
                        : false,
                ];
            });

        return $this->success([
            'exam' => [
                'id' => $model->id,
                'code' => $model->code,
                'judul' => $model->judul,
                'status' => $model->status,
                'status_label' => $model->statusLabel(),
                'study_group' => $model->studyGroup?->name,
                'subject' => $model->subject?->name,
                'jumlah_soal' => $model->jumlah_soal,
            ],
            'participants' => $participants,
        ]);
    }

    /**
     * POST /api/mobile/v1/ljk/exams/{exam}/scans — unggah & proses satu lembar.
     */
    public function scan(Request $request, string $exam): JsonResponse
    {
        $model = LjkExam::find($exam);
        if (! $model) {
            return $this->error('EXAM_NOT_FOUND', 'Ujian LJK tidak ditemukan.', 404);
        }

        if (! $this->access->canScan($request->user(), $model)) {
            return $this->error('FORBIDDEN', 'Anda tidak berwenang memindai ujian ini.', 403);
        }

        $maxKb = (int) config('ljk.process.max_upload_kb', 12288);

        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,webp|max:'.$maxKb,
            'participant_id' => 'nullable|uuid',
            'sheet_code' => 'nullable|string|max:40',
            'qr_payload' => 'nullable|string|max:500',
            'page' => 'nullable|integer|min:1|max:10',
        ]);

        $participant = null;
        if ($request->filled('participant_id')) {
            $participant = LjkParticipant::query()
                ->where('ljk_exam_id', $model->id)
                ->find($request->input('participant_id'));
        }

        $sheetCode = $request->input('sheet_code');

        if ($request->filled('qr_payload')) {
            $verified = $this->sheets->verifyQrPayload((string) $request->input('qr_payload'));
            if (! $verified) {
                return $this->error('QR_INVALID', 'QR lembar tidak valid atau bukan milik ujian ini.', 422);
            }
            $sheetCode = $verified['s'];
            $participant = null;
        }

        try {
            $result = $this->scans->storeAndProcess([
                'exam' => $model,
                'file' => $request->file('image'),
                'participant' => $participant,
                'sheet_code' => $sheetCode,
                'page' => $request->integer('page') ?: null,
                'source' => 'mobile',
            ], $request->user());
        } catch (RuntimeException $e) {
            return $this->error('SCAN_REJECTED', $e->getMessage(), 422);
        }

        $scan = $result['scan'];

        if ($scan->status !== LjkScan::STATUS_PROCESSED) {
            return $this->error(
                (string) ($scan->error_code ?: 'SCAN_FAILED'),
                (string) ($scan->error_message ?: 'Gambar tidak dapat diproses — ambil ulang.'),
                422
            );
        }

        return $this->success([
            'duplicate' => $result['duplicate'],
            'auto_sync' => $result['auto_sync'] ?? null,
            'scan' => $this->scanPayload($scan->loadMissing(['participant.student', 'sheet', 'answers'])),
        ]);
    }

    /**
     * GET /api/mobile/v1/ljk/scans/{scan}
     */
    public function scanDetail(Request $request, string $scan): JsonResponse
    {
        $model = LjkScan::with(['exam', 'participant.student', 'sheet', 'answers'])->find($scan);
        if (! $model || ! $model->exam) {
            return $this->error('SCAN_NOT_FOUND', 'Hasil pemindaian tidak ditemukan.', 404);
        }

        if (! $this->access->canView($request->user(), $model->exam)) {
            return $this->error('FORBIDDEN', 'Anda tidak berwenang melihat hasil ini.', 403);
        }

        return $this->success(['scan' => $this->scanPayload($model)]);
    }

    /**
     * PATCH /api/mobile/v1/ljk/scans/{scan}/answers/{answer} — koreksi guru.
     */
    public function correct(Request $request, string $scan, string $answer): JsonResponse
    {
        $scanModel = LjkScan::with('exam')->find($scan);
        $answerModel = LjkScanAnswer::with('participant')->find($answer);

        if (! $scanModel || ! $answerModel || (string) $answerModel->ljk_scan_id !== (string) $scanModel->id) {
            return $this->error('ANSWER_NOT_FOUND', 'Jawaban tidak ditemukan pada pemindaian ini.', 404);
        }

        $exam = $scanModel->exam;
        if (! $exam || ! $this->access->canReview($request->user(), $exam)) {
            return $this->error('FORBIDDEN', 'Anda tidak berwenang mengoreksi jawaban ini.', 403);
        }

        $data = $request->validate([
            'status' => 'required|in:terbaca,kosong,ganda,meragukan,tidak_dapat_diproses,manual',
            'label' => 'nullable|string|max:4',
            'point_final' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $updated = $this->review->correct($answerModel, [
                'status' => $data['status'],
                'label' => $data['label'] ?? null,
                'point_final' => $data['point_final'] ?? null,
            ], $request->user(), $data['reason'] ?? null);
        } catch (RuntimeException $e) {
            return $this->error('CORRECTION_REJECTED', $e->getMessage(), 422);
        }

        return $this->success([
            'answer' => [
                'id' => $updated->id,
                'nomor' => $updated->nomor,
                'status' => $updated->status,
                'selected_label' => $updated->selected_label,
                'is_correct' => $updated->is_correct,
                'needs_review' => (bool) $updated->needs_review,
                'review_action' => $updated->review_action,
            ],
            'nilai_akhir' => $updated->participant
                ? ($updated->participant->fresh()->nilai_akhir !== null ? (float) $updated->participant->fresh()->nilai_akhir : null)
                : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function scanPayload(LjkScan $scan): array
    {
        $counts = [];
        $needsReview = 0;

        foreach ($scan->answers as $answer) {
            $counts[$answer->status] = ($counts[$answer->status] ?? 0) + 1;
            if ($answer->needs_review) {
                $needsReview++;
            }
        }

        return [
            'id' => $scan->id,
            'status' => $scan->status,
            'sheet_code' => $scan->sheet?->sheet_code,
            'processing_ms' => $scan->processing_ms,
            'participant' => $scan->participant ? [
                'id' => $scan->participant->id,
                'nomor_peserta' => $scan->participant->nomor_peserta,
                'name' => $scan->participant->student?->name,
                'nilai_akhir' => $scan->participant->nilai_akhir !== null
                    ? (float) $scan->participant->nilai_akhir
                    : null,
            ] : null,
            'counts' => $counts,
            'needs_review' => $needsReview,
            'answers' => $scan->answers->map(fn (LjkScanAnswer $a) => [
                'id' => $a->id,
                'nomor' => $a->nomor,
                'jenis' => $a->jenis,
                'status' => $a->status,
                'status_label' => $a->statusLabel(),
                'selected' => $a->selected_label,
                'confidence' => (float) $a->confidence,
                'needs_review' => (bool) $a->needs_review,
                'is_correct' => $a->is_correct,
                'review_action' => $a->review_action,
            ])->values(),
        ];
    }
}
