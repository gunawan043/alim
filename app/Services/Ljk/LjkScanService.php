<?php

declare(strict_types=1);

namespace App\Services\Ljk;

use App\Models\LjkExam;
use App\Models\LjkParticipant;
use App\Models\LjkScan;
use App\Models\LjkScanAnswer;
use App\Models\LjkSheet;
use App\Models\User;
use App\Services\Ljk\Omr\OmrEngine;
use App\Services\Ljk\Omr\StripCodec;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Alur unggah & proses satu lembar: simpan gambar privat → jalankan mesin
 * OMR → simpan hasil baca → nilai.
 *
 * Target operasional v1: satu lembar per pemindaian (satu halaman).
 */
class LjkScanService
{
    public function __construct(
        private readonly OmrEngine $engine,
        private readonly LjkGradingService $grading,
        private readonly LjkTemplateService $templates,
        private readonly LjkSyncService $sync,
    ) {}

    /**
     * @param  array{exam:LjkExam, file:UploadedFile, participant?:?LjkParticipant, sheet_code?:?string, page?:?int, source?:string}  $input
     * @return array{scan: LjkScan, duplicate: bool}
     */
    public function storeAndProcess(array $input, User $actor): array
    {
        $exam = $input['exam'];
        $file = $input['file'];
        $source = (string) ($input['source'] ?? 'web');
        $page = $input['page'] ?? null;

        [$sheet, $participant] = $this->resolveSheet($exam, $input['participant'] ?? null, $input['sheet_code'] ?? null);

        $hash = hash_file('sha256', $file->getRealPath()) ?: '';

        // Idempotensi: gambar yang sama tidak diproses dua kali.
        $existing = LjkScan::query()
            ->where('sheet_id', $sheet->id)
            ->where('image_hash', $hash)
            ->where('status', LjkScan::STATUS_PROCESSED)
            ->first();

        if ($existing) {
            return [
                'scan' => $existing->load('answers'),
                'duplicate' => true,
                'auto_sync' => ['written' => false, 'reason' => 'duplicate', 'mode' => null],
            ];
        }

        $template = $sheet->template;
        if (! $template) {
            throw new RuntimeException('Template lembar tidak ditemukan.');
        }

        if ((int) $template->version !== (int) $sheet->template_version) {
            throw new RuntimeException('Versi template lembar tidak cocok — gunakan lembar yang sesuai.');
        }

        $this->templates->assertValidGeometry($template->geometry);

        $disk = (string) config('ljk.storage.scan_disk', 'local');
        $folder = trim((string) config('ljk.storage.scan_folder', 'ljk/scans'), '/')
            .'/'.($exam->id ?? $sheet->ljk_exam_id);
        $filename = $sheet->id.'-'.Str::uuid().'.'.($file->getClientOriginalExtension() ?: 'jpg');

        $path = $file->storeAs($folder, $filename, $disk);
        if (! $path) {
            throw new RuntimeException('Gagal menyimpan berkas pemindaian.');
        }

        $absolute = storage_path('app/'.ltrim($path, '/'));
        if ($disk === 'public') {
            $absolute = storage_path('app/public/'.ltrim($path, '/'));
        }

        $started = microtime(true);
        $result = $this->engine->process($absolute, $template->geometry, ['page' => $page]);
        $elapsed = (int) round((microtime(true) - $started) * 1000);

        [$imageWidth, $imageHeight] = $this->imageSize($absolute);
        $fileSizeKb = (int) round(($file->getSize() ?: 0) / 1024);

        // Verifikasi identitas data strip (satu lembar = satu peserta).
        $strip = $result['strip'] ?? null;
        $stripMatches = null;
        $resolvedParticipant = $participant;

        if (($strip['checksum_valid'] ?? false) === true) {
            $seq = (int) $strip['participant_seq'];

            if (! $resolvedParticipant) {
                $resolvedParticipant = LjkParticipant::query()
                    ->where('ljk_exam_id', $exam->id)
                    ->where('nomor_peserta', str_pad((string) $seq, 4, '0', STR_PAD_LEFT))
                    ->first();
            }

            if ($resolvedParticipant) {
                $stripMatches = ((int) $resolvedParticipant->nomor_peserta) === $seq;
            }
        }

        $quality = (array) ($result['quality'] ?? []);
        if ($stripMatches === false) {
            $quality['strip_mismatch'] = true;
        }

        $scan = DB::transaction(function () use ($sheet, $exam, $resolvedParticipant, $actor, $source, $path, $hash, $imageWidth, $imageHeight, $fileSizeKb, $quality, $result, $elapsed, $stripMatches) {
            $status = ($result['ok'] ?? false) ? LjkScan::STATUS_PROCESSED : LjkScan::STATUS_FAILED;

            $scan = LjkScan::create([
                'ljk_exam_id' => $exam->id,
                'sheet_id' => $sheet->id,
                'participant_id' => $resolvedParticipant?->id,
                'uploaded_by' => $actor->id,
                'source' => $source,
                'image_path' => $path,
                'image_hash' => $hash,
                'image_width' => $imageWidth,
                'image_height' => $imageHeight,
                'file_size_kb' => $fileSizeKb,
                'quality' => $quality,
                'status' => $status,
                'error_code' => $result['error_code'] ?? null,
                'error_message' => $result['error_message'] ?? null,
                'engine_version' => $result['engine_version'] ?? null,
                'processing_ms' => $elapsed,
                'scanned_at' => now(),
            ]);

            if (! ($result['ok'] ?? false)) {
                return $scan;
            }

            // Jawaban: simpan hasil baca awal per nomor.
            foreach ($result['questions'] as $question) {
                $needsReview = (bool) ($question['needs_review'] ?? true);

                // Ketidaksesuaian identitas strip → seluruh nomor wajib diperiksa.
                if ($stripMatches === false || ($stripMatches === null && ($quality['strip_mismatch'] ?? false))) {
                    $needsReview = true;
                }

                LjkScanAnswer::create([
                    'ljk_scan_id' => $scan->id,
                    'ljk_exam_id' => $exam->id,
                    'participant_id' => $resolvedParticipant?->id,
                    'nomor' => (int) $question['nomor'],
                    'item_id' => null,
                    'soal_id' => null,
                    'jenis' => (string) ($question['jenis'] ?? 'pg'),
                    'opsi' => array_values(array_map(static fn ($q) => (string) ($q['label'] ?? ''), $question['options'] ?? [])),
                    'status' => (string) $question['status'],
                    'selected_label' => $question['selected'] ?? null,
                    'confidence' => (float) ($question['confidence'] ?? 0),
                    'evidence' => $this->compactEvidence($question),
                    'needs_review' => $needsReview,
                ]);
            }

            // Scan lama untuk lembar yang sama digantikan (riwayat tetap ada).
            LjkScan::query()
                ->where('sheet_id', $sheet->id)
                ->where('id', '!=', $scan->id)
                ->where('status', LjkScan::STATUS_PROCESSED)
                ->update(['status' => LjkScan::STATUS_SUPERSEDED]);

            $sheet->forceFill(['status' => LjkSheet::STATUS_SCANNED])->save();

            return $scan;
        });

        $autoSync = ['written' => false, 'reason' => 'skipped', 'mode' => null];

        if ($scan->status === LjkScan::STATUS_PROCESSED) {
            $this->fillItemReferences($scan, $resolvedParticipant);

            $this->grading->gradeScan($scan);

            if ($resolvedParticipant) {
                $this->grading->recomputeParticipant($resolvedParticipant);

                // Nilai langsung masuk ke kolom resmi bila hasilnya sudah bersih.
                $autoSync = $this->sync->autoSyncParticipant($exam, $resolvedParticipant->fresh(), $actor);
            }

            $this->updateExamStatus($exam);

            activity()
                ->performedOn($scan)
                ->causedBy($actor)
                ->withProperties([
                    'exam' => $exam->code,
                    'sheet' => $sheet->sheet_code,
                    'status' => $scan->status,
                    'processing_ms' => $scan->processing_ms,
                ])
                ->log('ljk.scan.processed');
        }

        return ['scan' => $scan->fresh(['answers']), 'duplicate' => false, 'auto_sync' => $autoSync];
    }

    /**
     * @return array{0: LjkSheet, 1: ?LjkParticipant}
     */
    public function resolveSheet(LjkExam $exam, ?LjkParticipant $participant, ?string $sheetCode): array
    {
        if (! $participant && $sheetCode) {
            $sheet = LjkSheet::query()
                ->where('ljk_exam_id', $exam->id)
                ->where('sheet_code', $sheetCode)
                ->first();

            if ($sheet) {
                return [$sheet, $sheet->participant()->first()];
            }

            throw new RuntimeException('Kode lembar tidak dikenali pada ujian ini.');
        }

        if ($participant) {
            $sheet = LjkSheet::query()->where('participant_id', $participant->id)->first();
            if (! $sheet) {
                throw new RuntimeException('Lembar belum diterbitkan untuk peserta ini.');
            }

            return [$sheet, $participant];
        }

        throw new RuntimeException('Pilih peserta atau pindai QR/kode lembar terlebih dahulu.');
    }

    /**
     * Isi referensi butir paket pada jawaban (item_id/soal_id) sesuai pemetaan.
     */
    public function fillItemReferences(LjkScan $scan, ?LjkParticipant $participant): void
    {
        if (! $participant) {
            return;
        }

        $mapping = collect($participant->mappingItems())->keyBy('no');

        foreach ($scan->answers()->get() as $answer) {
            $mapItem = $mapping->get($answer->nomor);
            if (! $mapItem) {
                continue;
            }

            $answer->forceFill([
                'item_id' => $mapItem['item_id'] ?? null,
                'soal_id' => $mapItem['soal_id'] ?? null,
                'jenis' => (string) ($mapItem['jenis'] ?? $answer->jenis),
            ])->save();
        }
    }

    /**
     * Perbarui status ujian mengikuti kondisi pemindaian.
     */
    public function updateExamStatus(LjkExam $exam): void
    {
        $exam->refresh();

        if (in_array($exam->status, [LjkExam::STATUS_FINAL, LjkExam::STATUS_TERSINKRON, LjkExam::STATUS_DIBATALKAN], true)) {
            return;
        }

        $summary = $this->grading->reviewSummary($exam);

        $status = LjkExam::STATUS_PENGUMPULAN;
        if ($summary['scanned'] > 0 && $summary['needs_review'] === 0 && $summary['scanned'] === $summary['sheets']) {
            $status = LjkExam::STATUS_FINAL;
        } elseif ($summary['scanned'] > 0 && $summary['needs_review'] > 0) {
            $status = LjkExam::STATUS_REVIEW;
        }

        $exam->forceFill(['status' => $status])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function compactEvidence(array $question): array
    {
        $options = [];
        foreach ($question['opsi'] ?? [] as $label => $features) {
            $options[(string) $label] = [
                'score' => (float) ($features['score'] ?? 0),
                'ink' => (float) ($features['ink_ratio'] ?? 0),
                'diag' => [
                    (float) ($features['diag1'] ?? 0),
                    (float) ($features['diag2'] ?? 0),
                ],
                'corner' => (float) ($features['corner_score'] ?? 0),
                'center' => (float) ($features['center_score'] ?? 0),
                'uniformity' => (float) ($features['uniformity'] ?? 0),
                'edge_mid' => (float) ($features['edge_mid'] ?? 0),
                'blue' => $features['ink_blue_ratio'] ?? null,
                'signature' => $features['signature'] ?? [],
            ];
        }

        return [
            'options' => $options,
            'slot' => $question['selected'] ?? null,
            'selected_set' => $question['selected_set'] ?? [],
            'top' => $question['evidence']['top'] ?? null,
            'top_score' => $question['evidence']['top_score'] ?? null,
            'second_score' => $question['evidence']['second_score'] ?? null,
        ];
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function imageSize(string $absolutePath): array
    {
        $info = @getimagesize($absolutePath);

        return $info ? [(int) $info[0], (int) $info[1]] : [0, 0];
    }
}
