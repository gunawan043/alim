<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ljk;

use App\Http\Controllers\Concerns\AuthorizesAcademicScope;
use App\Http\Controllers\Controller;
use App\Models\LjkExam;
use App\Models\LjkParticipant;
use App\Models\LjkScan;
use App\Services\Ljk\LjkAccess;
use App\Services\Ljk\LjkSheetService;
use App\Services\Ljk\LjkScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pemindaian LJK: halaman kamera (browser HP) + endpoint unggah + gambar privat.
 */
class LjkScanController extends Controller
{
    use AuthorizesAcademicScope;

    public function __construct(
        private readonly LjkScanService $scans,
        private readonly LjkSheetService $sheets,
        private readonly LjkAccess $access,
    ) {}

    /**
     * Halaman pemindai (kamera HP).
     */
    public function page(Request $request, string $userId, string $ljkUuid)
    {
        $exam = LjkExam::findOrFail($ljkUuid);
        $this->ensureSchoolScope($request, $exam->school_id);

        if (! $this->access->canScan($request->user(), $exam)) {
            abort(403, 'Anda tidak berwenang memindai ujian ini.');
        }

        $participants = LjkParticipant::query()
            ->where('ljk_exam_id', $exam->id)
            ->with(['student:id,name,nis', 'sheet:id,participant_id,sheet_code,status'])
            ->orderBy('nomor_peserta')
            ->get();

        return view('ljk.scan', compact('exam', 'participants', 'userId'));
    }

    /**
     * Terima gambar dari kamera HP dan proses OMR.
     */
    public function store(Request $request, string $userId, string $ljkUuid): JsonResponse
    {
        $exam = LjkExam::findOrFail($ljkUuid);
        $this->ensureSchoolScope($request, $exam->school_id);

        if (! $this->access->canScan($request->user(), $exam)) {
            return $this->error('Anda tidak berwenang memindai ujian ini.', 403);
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
                ->where('ljk_exam_id', $exam->id)
                ->find($request->input('participant_id'));
        }

        $sheetCode = $request->input('sheet_code');

        // QR dipindai lebih dulu (html5-qrcode mengirim payload JSON).
        if ($request->filled('qr_payload')) {
            $verified = $this->sheets->verifyQrPayload((string) $request->input('qr_payload'));
            if (! $verified) {
                return $this->error('QR lembar tidak valid atau bukan milik ujian ini.', 422);
            }
            $sheetCode = $verified['s'];
            $participant = null;
        }

        try {
            $result = $this->scans->storeAndProcess([
                'exam' => $exam,
                'file' => $request->file('image'),
                'participant' => $participant,
                'sheet_code' => $sheetCode,
                'page' => $request->integer('page') ?: null,
                'source' => $request->input('source', 'web'),
            ], $request->user());
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $scan = $result['scan'];
        $scan->loadMissing(['participant.student', 'sheet']);

        if ($scan->status !== LjkScan::STATUS_PROCESSED) {
            return response()->json([
                'success' => false,
                'duplicate' => false,
                'message' => $scan->error_message ?: 'Gambar tidak dapat diproses.',
                'error' => [
                    'code' => $scan->error_code,
                    'quality' => $scan->quality,
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'duplicate' => $result['duplicate'],
            'data' => [
                'scan_id' => $scan->id,
                'exam_code' => $exam->code,
                'sheet_code' => $scan->sheet?->sheet_code,
                'participant' => $scan->participant ? [
                    'id' => $scan->participant->id,
                    'nomor_peserta' => $scan->participant->nomor_peserta,
                    'name' => $scan->participant->student?->name,
                    'nilai_akhir' => $scan->participant->nilai_akhir,
                ] : null,
                'quality' => [
                    'sharpness' => $scan->quality['sharpness'] ?? null,
                    'brightness' => $scan->quality['brightness'] ?? null,
                    'markers_found' => $scan->quality['markers_found'] ?? null,
                ],
                'summary' => $this->summary($scan),
                'auto_sync' => $result['auto_sync'] ?? null,
                'answers' => $scan->answers->map(fn ($a) => [
                    'nomor' => $a->nomor,
                    'status' => $a->status,
                    'status_label' => $a->statusLabel(),
                    'selected' => $a->selected_label,
                    'needs_review' => (bool) $a->needs_review,
                    'is_correct' => $a->is_correct,
                ])->values(),
            ],
        ]);
    }

    /**
     * Gambar scan (privat — hanya lewat route ber-otorisasi).
     */
    public function image(Request $request, string $userId, string $scanUuid): StreamedResponse|HttpResponse
    {
        $scan = LjkScan::with('exam')->findOrFail($scanUuid);
        $this->ensureSchoolScope($request, $scan->exam?->school_id);

        if (! $scan->exam || ! $this->access->canView($request->user(), $scan->exam)) {
            abort(403);
        }

        $disk = (string) config('ljk.storage.scan_disk', 'local');

        if (! Storage::disk($disk)->exists($scan->image_path)) {
            abort(404);
        }

        return Storage::disk($disk)->response($scan->image_path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(LjkScan $scan): array
    {
        $counts = array_fill_keys(array_keys(config('ljk.statuses', [])), 0);
        $needsReview = 0;

        foreach ($scan->answers as $answer) {
            $counts[$answer->status] = ($counts[$answer->status] ?? 0) + 1;
            if ($answer->needs_review) {
                $needsReview++;
            }
        }

        return [
            'total' => $scan->answers->count(),
            'counts' => $counts,
            'needs_review' => $needsReview,
            'processing_ms' => $scan->processing_ms,
        ];
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
