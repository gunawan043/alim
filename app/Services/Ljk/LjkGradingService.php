<?php

declare(strict_types=1);

namespace App\Services\Ljk;

use App\Models\LjkExam;
use App\Models\LjkParticipant;
use App\Models\LjkScan;
use App\Models\LjkScanAnswer;
use App\Models\PaketSoalItem;
use Illuminate\Support\Collection;

/**
 * Penilaian: mencocokkan hasil baca OMR dengan kunci dari versi paket yang
 * benar (konten_snapshot pada saat LJK diterbitkan), menerapkan bobot, dan
 * menghitung nilai peserta.
 *
 * Kunci jawaban TIDAK pernah dikirim ke klien; hanya dipakai di server.
 */
class LjkGradingService
{
    /**
     * Nilai seluruh jawaban satu scan + perbarui total peserta.
     */
    public function gradeScan(LjkScan $scan): void
    {
        $scan->loadMissing(['answers', 'participant']);

        $participant = $scan->participant;
        $mapping = $participant ? collect($participant->mappingItems())->keyBy('no') : collect();

        foreach ($scan->answers as $answer) {
            $this->gradeAnswer($answer, $mapping->get($answer->nomor));
        }

        if ($participant) {
            $this->recomputeParticipant($participant);
        }
    }

    /**
     * @param  array<string, mixed>|null  $mapItem
     */
    public function gradeAnswer(LjkScanAnswer $answer, ?array $mapItem): void
    {
        $answer->loadMissing('scan.exam.paketSoal');

        $jenis = (string) ($mapItem['jenis'] ?? $answer->jenis);

        if ($jenis === LjkScanAnswer::MANUAL || in_array($jenis, config('ljk.grading.manual_types', []), true)) {
            $answer->forceFill([
                'jenis' => $jenis,
                'is_correct' => null,
                'point_awal' => 0,
                'point_final' => $answer->review_action === 'corrected' ? (float) $answer->point_final : 0,
                'needs_review' => $answer->review_action === 'corrected' ? false : true,
            ])->save();

            return;
        }

        $bobot = (float) ($mapItem['bobot'] ?? 0.0);
        $keys = $this->keyLabels($mapItem['item_id'] ?? null);

        $isCorrected = $answer->review_action === 'corrected';
        $selected = $isCorrected
            ? $answer->selected_label
            : $this->resolveSelected($answer, $mapItem);

        $autoTypes = (array) config('ljk.grading.auto_types', ['pg', 'mcc', 'bs']);

        $isCorrect = null;
        $points = 0.0;
        $resolvedSet = [];

        if (in_array($jenis, $autoTypes, true) && ($answer->status === LjkScanAnswer::TERBACA || $answer->review_action === 'corrected') && $keys !== []) {
            if ($jenis === 'mcc') {
                $resolvedSet = $this->resolveSet($answer, $mapItem);
                $sortedResolved = $resolvedSet;
                sort($sortedResolved);
                sort($keys);
                $isCorrect = $sortedResolved === $keys;
            } else {
                $isCorrect = $selected !== null && in_array($selected, $keys, true);
            }

            $points = $isCorrect ? $bobot : 0.0;
        }

        $evidence = (array) $answer->evidence;
        $evidence['resolved_label'] = $selected;
        if ($resolvedSet !== []) {
            $evidence['resolved_set'] = $resolvedSet;
        }
        $evidence['key_labels'] = $keys;

        // Nilai final: untuk koreksi label, biarkan penilaian ulang menghitung
        // point_final; hanya skor manual eksplisit yang dipatok.
        $manualPoint = ($answer->review_action === 'corrected')
            && (bool) (($answer->evidence['manual_point_final'] ?? false));

        $finalPoint = $manualPoint ? (float) $answer->point_final : round($points, 2);

        $answer->forceFill([
            'jenis' => $jenis,
            'selected_label' => $selected,
            'evidence' => $evidence,
            'is_correct' => $isCorrect,
            'point_awal' => round($points, 2),
            'point_final' => $finalPoint,
        ])->save();
    }

    /**
     * Ubah himpunan slot (A..E) menjadi label opsi paket.
     *
     * @param  array<string, mixed>|null  $mapItem
     * @return array<int, string>
     */
    public function resolveSet(LjkScanAnswer $answer, ?array $mapItem): array
    {
        $slots = (array) ($answer->evidence['selected_set'] ?? []);
        $labels = array_values((array) ($mapItem['opsi'] ?? []));
        $out = [];

        foreach ($slots as $slot) {
            $index = ord(strtoupper((string) $slot)) - 65;
            $out[] = $labels[$index] ?? (string) $slot;
        }

        return array_values(array_unique($out));
    }

    /**
     * Label kunci dari snapshot paket (versi yang dibekukan).
     *
     * @return array<int, string>
     */
    public function keyLabels(?string $itemId): array
    {
        if (! $itemId) {
            return [];
        }

        $item = PaketSoalItem::with(['soal.options'])->find($itemId);
        if (! $item) {
            return [];
        }

        $konten = $item->kontenFinal();
        $keys = [];
        foreach ($konten['opsi'] ?? [] as $option) {
            if (($option['kunci'] ?? false) === true) {
                $keys[] = (string) ($option['label'] ?? '');
            }
        }

        return array_values(array_filter($keys, static fn ($l) => $l !== ''));
    }

    /**
     * Ubah label kotak (A..E) menjadi label opsi paket sesuai pemetaan peserta.
     *
     * Slot mentah dari mesin tersimpan di evidence.slot; `selected_label`
     * menyimpan label opsi paket yang sudah dipetakan (atau hasil koreksi guru).
     */
    public function resolveSelected(LjkScanAnswer $answer, ?array $mapItem): ?string
    {
        $slot = $answer->evidence['slot'] ?? $answer->selected_label;

        if (! $slot || ! $mapItem) {
            return $slot;
        }

        $labels = array_values((array) ($mapItem['opsi'] ?? []));
        $index = ord(strtoupper((string) $slot)) - 65;

        if ($index >= 0 && isset($labels[$index])) {
            return (string) $labels[$index];
        }

        return (string) $slot;
    }

    /**
     * Hitung skor akhir peserta dari scan terbaru yang valid.
     */
    public function recomputeParticipant(LjkParticipant $participant): void
    {
        $scan = $this->latestScan($participant);
        if (! $scan) {
            return;
        }

        $exam = $participant->exam ?? $participant->exam()->first();
        $scale = (float) (($exam?->skor_config['scale'] ?? null) ?: config('ljk.grading.scale', 100.0));
        $totalWeight = (float) ($exam?->skor_config['total_weight'] ?? 0);

        $answers = $scan->answers()->get();
        $points = (float) $answers->sum(static fn ($a) => (float) $a->point_final);

        $nilai = null;
        if ($totalWeight > (float) config('ljk.grading.min_total_weight', 0.01)) {
            $nilai = round(($points / $totalWeight) * $scale, 2);
        }

        $participant->forceFill([
            'total_point' => (int) round($points),
            'nilai_akhir' => $nilai,
            'status' => $this->participantStatus($answers, $participant),
        ])->save();
    }

    /**
     * Scan terbaru yang dipakai sebagai sumber nilai peserta.
     */
    public function latestScan(LjkParticipant $participant): ?LjkScan
    {
        return LjkScan::query()
            ->where('participant_id', $participant->id)
            ->where('status', LjkScan::STATUS_PROCESSED)
            ->orderByDesc('scanned_at')
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * @param  Collection<int, LjkScanAnswer>  $answers
     */
    private function participantStatus(Collection $answers, LjkParticipant $participant): string
    {
        if ($answers->isEmpty()) {
            return $participant->status;
        }

        $needsReview = $answers->contains(static fn (LjkScanAnswer $a) => (bool) $a->needs_review);

        return $needsReview ? LjkParticipant::STATUS_DIPINDAI : LjkParticipant::STATUS_DIVERIFIKASI;
    }

    /**
     * Ringkasan kebutuhan pemeriksaan sebuah ujian.
     *
     * @return array{sheets: int, scanned: int, needs_review: int, clean: int, manual: int}
     */
    public function reviewSummary(LjkExam $exam): array
    {
        $participants = LjkParticipant::query()->where('ljk_exam_id', $exam->id)->get();

        $scanned = 0;
        $needsReview = 0;
        $clean = 0;
        $manual = 0;

        foreach ($participants as $participant) {
            $scan = $this->latestScan($participant);
            if (! $scan) {
                continue;
            }

            $scanned++;
            $answers = $scan->answers()->get();
            $manual += $answers->where('status', LjkScanAnswer::MANUAL)->count();
            $hasReview = $answers->contains(static fn (LjkScanAnswer $a) => (bool) $a->needs_review);

            if ($hasReview) {
                $needsReview++;
            } else {
                $clean++;
            }
        }

        return [
            'sheets' => $participants->count(),
            'scanned' => $scanned,
            'needs_review' => $needsReview,
            'clean' => $clean,
            'manual' => $manual,
        ];
    }
}
