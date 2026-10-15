<?php

declare(strict_types=1);

namespace App\Services\Ljk;

use App\Models\LjkAnswerCorrection;
use App\Models\LjkParticipant;
use App\Models\LjkScanAnswer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Koreksi guru: hasil baca awal tetap tersimpan di ljk_scan_answers,
 * setiap perubahan dicatat append-only di ljk_answer_corrections
 * (aktor, waktu, nilai sebelum/sesudah, alasan).
 */
class LjkReviewService
{
    public function __construct(
        private readonly LjkGradingService $grading,
        private readonly LjkScanService $scans,
        private readonly LjkSyncService $sync,
    ) {}

    /**
     * Koreksi jawaban hasil baca.
     *
     * @param  array{status?:string, label?:?string, point_final?:float|int|null, reason?:?string}  $after
     */
    public function correct(LjkScanAnswer $answer, array $after, User $actor, ?string $reason = null): LjkScanAnswer
    {
        $answer->loadMissing(['scan', 'participant']);

        $status = (string) ($after['status'] ?? $answer->status);
        if (! in_array($status, LjkScanAnswer::STATUSES, true)) {
            throw new RuntimeException('Status jawaban tidak dikenal.');
        }

        $participant = $answer->participant;
        $mapItem = $participant
            ? collect($participant->mappingItems())->firstWhere('no', $answer->nomor)
            : null;

        $label = array_key_exists('label', $after) ? $after['label'] : $answer->selected_label;
        $label = $label === null || $label === '' ? null : strtoupper((string) $label);

        if ($label !== null && $mapItem) {
            $allowed = array_map(static fn ($l) => strtoupper((string) $l), (array) ($mapItem['opsi'] ?? []));
            if (! in_array($label, $allowed, true)) {
                throw new RuntimeException('Opsi yang dipilih tidak ada pada soal ini.');
            }
        }

        $before = [
            'status' => $answer->status,
            'selected' => $answer->selected_label,
            'point_final' => (float) $answer->point_final,
        ];

        $pointFinal = $after['point_final'] ?? null;
        if ($pointFinal !== null) {
            $bobot = (float) ($mapItem['bobot'] ?? 0);
            $pointFinal = max(0.0, min((float) $bobot, (float) $pointFinal));
        }

        DB::transaction(function () use ($answer, $status, $label, $pointFinal, $actor, $reason, $before) {
            $evidence = (array) $answer->evidence;
            $evidence['manual_point_final'] = $pointFinal !== null;

            $answer->forceFill([
                'status' => $status,
                'selected_label' => $label,
                'review_action' => 'corrected',
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'needs_review' => false,
                'teacher_note' => $reason,
                'evidence' => $evidence,
                'point_final' => $pointFinal ?? $answer->point_final,
            ])->save();

            LjkAnswerCorrection::create([
                'ljk_scan_answer_id' => $answer->id,
                'ljk_exam_id' => $answer->ljk_exam_id,
                'participant_id' => $answer->participant_id,
                'nomor' => $answer->nomor,
                'before' => $before,
                'after' => [
                    'status' => $status,
                    'selected' => $label,
                    'point_final' => $pointFinal !== null ? (float) $pointFinal : (float) $answer->point_final,
                ],
                'reason' => $reason,
                'corrected_by' => $actor->id,
            ]);
        });

        // Nilai ulang jawaban ini (kunci tetap dari versi paket yang benar).
        $this->grading->gradeAnswer($answer->fresh(), $mapItem);

        if ($participant) {
            $fresh = $participant->fresh();
            $this->grading->recomputeParticipant($fresh);
            $exam = $participant->exam()->first();
            $this->scans->updateExamStatus($exam);

            // Koreksi yang membuat hasil bersih → nilai ikut diperbarui otomatis.
            if ($exam) {
                $this->sync->autoSyncParticipant($exam, $fresh->fresh(), $actor);
            }
        }

        return $answer->fresh();
    }

    /**
     * Terima hasil baca tanpa perubahan (tanda "sudah diperiksa").
     */
    public function accept(LjkScanAnswer $answer, User $actor): LjkScanAnswer
    {
        $answer->forceFill([
            'review_action' => 'accepted',
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'needs_review' => false,
        ])->save();

        if ($answer->participant_id) {
            $participant = LjkParticipant::find($answer->participant_id);
            if ($participant) {
                $this->grading->recomputeParticipant($participant);

                $exam = $participant->exam()->first();
                if ($exam) {
                    $this->sync->autoSyncParticipant($exam, $participant->fresh(), $actor);
                }
            }
        }

        return $answer->fresh();
    }
}
