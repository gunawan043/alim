<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ljk\LjkTemplateService;
use App\Services\Ljk\Omr\OmrEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Ukur performa mesin OMR pada dataset berlabel dan (opsional) telusuri
 * ambang batas terbaik.
 *
 * Metrik: exact-sheet accuracy, per-question accuracy, false acceptance,
 * review rate, dan waktu pemrosesan.
 *
 * Kasus yang tidak dapat dibaca dengan yakin WAJIB diarahkan ke pemeriksaan
 * guru; metrik review rate menunjukkan proporsi tersebut.
 */
class LjkCalibrateCommand extends Command
{
    protected $signature = 'ljk:calibrate
        {--dataset=storage/app/ljk/datasets/demo : Direktori dataset berlabel}
        {--questions=20 : Jumlah soal template (harus sama dengan dataset)}
        {--options=4 : Jumlah opsi template}
        {--tune : Telusuri ambang option_strong/option_weak}
        {--json : Keluarkan hasil sebagai JSON}';

    protected $description = 'Ukur akurasi & review rate mesin OMR LJK pada dataset berlabel';

    /** @var array<int, array<string, mixed>> */
    private array $misreads = [];

    /** @var array<int, array<string, mixed>> */
    private array $lastSheetMisreads = [];

    public function handle(): int
    {
        // Mesin OMR memproses citra di memori; pastikan ruang cukup.
        if ((int) ini_get('memory_limit') !== -1 && (int) ini_get('memory_limit') < 512) {
            ini_set('memory_limit', '512M');
        }

        $dir = base_path((string) $this->option('dataset'));
        $labelsPath = $dir.'/labels.json';

        if (! File::exists($labelsPath)) {
            $this->error("labels.json tidak ditemukan di {$dir}.");

            return self::FAILURE;
        }

        $dataset = json_decode((string) File::get($labelsPath), true);
        $images = $dataset['images'] ?? [];
        if ($images === []) {
            $this->error('Dataset kosong.');

            return self::FAILURE;
        }

        $questions = (int) ($dataset['questions'] ?? $this->option('questions'));
        $options = (int) ($dataset['options'] ?? $this->option('options'));

        $specs = [];
        for ($i = 1; $i <= $questions; $i++) {
            $specs[] = ['nomor' => $i, 'jenis' => 'pg', 'options' => $options];
        }

        $geometry = app(LjkTemplateService::class)->buildGeometry($specs);
        $engine = app(OmrEngine::class);

        if ($this->option('tune')) {
            return $this->tune($engine, $geometry, $images, $dir);
        }

        $metrics = $this->evaluate($engine, $geometry, $images, $dir);

        if ($this->option('json')) {
            $this->line(json_encode($metrics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->renderMetrics($metrics);

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<string, mixed>>  $images
     * @return array<string, mixed>
     */
    private function evaluate(OmrEngine $engine, array $geometry, array $images, string $dir): array
    {
        $perQuestion = [];
        $sheetExact = 0;
        $sheetTotal = 0;
        $falseAcceptance = 0;
        $reviewedQuestions = 0;
        $durations = [];
        $failedSheets = 0;
        $failReasons = [];
        $this->misreads = [];

        foreach ($images as $entry) {
            $file = $entry['file'] ?? null;
            $expected = array_map('strval', $entry['answers'] ?? []);
            if (! $file || ! File::exists($dir.'/'.$file)) {
                continue;
            }

            $sheetTotal++;
            $result = $engine->process($dir.'/'.$file, $geometry);
            $durations[] = (int) ($result['duration_ms'] ?? 0);

            if (! ($result['ok'] ?? false)) {
                $failedSheets++;
                $reason = (string) ($result['error_code'] ?? 'unknown');
                $failReasons[$reason] = ($failReasons[$reason] ?? 0) + 1;
                // Semua nomor dianggap masuk pemeriksaan guru (bukan false accept).
                $reviewedQuestions += count($expected);

                continue;
            }

            $sheetCorrect = true;
            $this->lastSheetMisreads = [];

            foreach ($result['questions'] as $question) {
                $nomor = (int) $question['nomor'];
                $want = $expected[$nomor] ?? '';

                $perQuestion[$nomor] = $perQuestion[$nomor] ?? ['total' => 0, 'correct' => 0];
                $perQuestion[$nomor]['total']++;

                $read = null;
                if ($question['status'] === 'terbaca') {
                    $read = (string) $question['selected'];
                } elseif ($question['status'] === 'kosong') {
                    $read = '';
                } elseif ($question['status'] === 'ganda') {
                    // Ganda: bandingkan sebagai himpunan huruf.
                    $readSet = array_map('strval', (array) ($question['selected_set'] ?? []));
                    sort($readSet);
                    $read = implode('', $readSet);
                }

                $isCorrect = false;

                if ($want === '?') {
                    // Label '?' = tanda di luar format: benar bila mesin TIDAK
                    // mengklaim terbaca yakin (masuk pemeriksaan guru).
                    $isCorrect = in_array($question['status'], ['meragukan', 'ganda', 'tidak_dapat_diproses', 'kosong'], true);
                } elseif (strlen($want) <= 1) {
                    $isCorrect = $read === $want;
                } else {
                    // Label ganda: bandingkan sebagai himpunan huruf.
                    $wantSet = str_split($want);
                    $readSet = $read !== null ? str_split($read) : [];
                    sort($wantSet);
                    sort($readSet);
                    $isCorrect = $wantSet === $readSet;
                }

                // False acceptance: mesin yakin terbaca, tetapi tidak sesuai label.
                // False acceptance: mesin mengklaim TERBACA, tetapi tidak
                // sesuai label pasti (termasuk saat label '?').
                if ($question['status'] === 'terbaca' && ! $isCorrect) {
                    $falseAcceptance++;
                }

                if ($question['needs_review'] ?? false) {
                    $reviewedQuestions++;
                }

                if ($isCorrect) {
                    $perQuestion[$nomor]['correct']++;
                } else {
                    $sheetCorrect = false;
                    $this->lastSheetMisreads[] = [
                        'nomor' => $nomor,
                        'want' => $want,
                        'got' => $read,
                        'status' => $question['status'],
                        'confidence' => $question['confidence'] ?? null,
                    ];
                }
            }

            if (! $sheetCorrect) {
                $this->misreads[] = ['file' => $file] + $this->lastSheetMisreads;
            }

            if ($sheetCorrect) {
                $sheetExact++;
            }
        }

        $totalQuestions = 0;
        $correctQuestions = 0;
        foreach ($perQuestion as $stats) {
            $totalQuestions += $stats['total'];
            $correctQuestions += $stats['correct'];
        }

        sort($durations);

        return [
            'engine_version' => (string) config('ljk.engine_version'),
            'dataset' => $dir,
            'sheets' => $sheetTotal,
            'sheets_exact' => $sheetExact,
            'exact_sheet_accuracy' => $sheetTotal > 0 ? round($sheetExact / $sheetTotal, 4) : 0.0,
            'questions' => $totalQuestions,
            'questions_correct' => $correctQuestions,
            'per_question_accuracy' => $totalQuestions > 0 ? round($correctQuestions / $totalQuestions, 4) : 0.0,
            'false_acceptance' => $falseAcceptance,
            'false_acceptance_rate' => $totalQuestions > 0 ? round($falseAcceptance / $totalQuestions, 4) : 0.0,
            'review_rate' => $totalQuestions > 0 ? round($reviewedQuestions / $totalQuestions, 4) : 0.0,
            'failed_sheets' => $failedSheets,
            'fail_reasons' => $failReasons,
            'processing_ms' => [
                'min' => $durations[0] ?? 0,
                'median' => $durations[intdiv(max(0, count($durations) - 1), 2)] ?? 0,
                'max' => $durations[count($durations) - 1] ?? 0,
                'avg' => count($durations) > 0 ? (int) round(array_sum($durations) / count($durations)) : 0,
            ],
            'per_question' => $perQuestion,
            'misreads' => array_slice($this->misreads, 0, 40),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $images
     */
    private function tune(OmrEngine $engine, array $geometry, array $images, string $dir): int
    {
        $strongs = [0.46, 0.50, 0.54, 0.58];
        $weaks = [0.24, 0.28, 0.33, 0.38];

        $this->info('Menelusuri ambang batas…');
        $rows = [];

        foreach ($strongs as $strong) {
            foreach ($weaks as $weak) {
                if ($weak >= $strong) {
                    continue;
                }

                config(['ljk.detection.option_strong' => $strong, 'ljk.detection.option_weak' => $weak]);
                $metrics = $this->evaluate($engine, $geometry, $images, $dir);

                $rows[] = [
                    'strong' => $strong,
                    'weak' => $weak,
                    'exact' => $metrics['exact_sheet_accuracy'],
                    'per_q' => $metrics['per_question_accuracy'],
                    'false_accept' => $metrics['false_acceptance_rate'],
                    'review' => $metrics['review_rate'],
                ];
            }
        }

        usort($rows, static fn ($a, $b) => [$b['exact'], $b['per_q'], $a['false_accept'], $a['review']]
            <=> [$a['exact'], $a['per_q'], $b['false_accept'], $b['review']]);

        $this->table(
            ['option_strong', 'option_weak', 'exact_sheet', 'per_question', 'false_accept', 'review_rate'],
            array_map(static fn ($r) => [
                $r['strong'], $r['weak'], $r['exact'], $r['per_q'], $r['false_accept'], $r['review'],
            ], array_slice($rows, 0, 8))
        );

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $m
     */
    private function renderMetrics(array $m): void
    {
        $this->newLine();
        $this->info('=== LJK OMR Calibration ===');
        $this->table(['Metrik', 'Nilai'], [
            ['Engine', $m['engine_version']],
            ['Dataset', $m['dataset']],
            ['Lembar', $m['sheets']],
            ['Exact-sheet accuracy', number_format((float) $m['exact_sheet_accuracy'] * 100, 2).'%'],
            ['Per-question accuracy', number_format((float) $m['per_question_accuracy'] * 100, 2).'%'],
            ['False acceptance rate', number_format((float) $m['false_acceptance_rate'] * 100, 2).'%'],
            ['Review rate', number_format((float) $m['review_rate'] * 100, 2).'%'],
            ['Lembar gagal diproses', $m['failed_sheets']],
            ['Waktu proses (min/median/max)', sprintf('%d / %d / %d ms', $m['processing_ms']['min'], $m['processing_ms']['median'], $m['processing_ms']['max'])],
        ]);

        if (($m['fail_reasons'] ?? []) !== []) {
            $this->warn('Alasan gagal: '.json_encode($m['fail_reasons']));
        }

        if ($this->output->isVerbose() && ($m['misreads'] ?? []) !== []) {
            $this->newLine();
            $this->warn('Detail kesalahan baca (maks 40 lembar):');
            foreach ($m['misreads'] as $sheet) {
                $this->line('  '.$sheet['file']);
                foreach (array_slice($sheet, 1) as $miss) {
                    if (is_array($miss)) {
                        $this->line(sprintf(
                            '    #%d want=%s got=%s status=%s conf=%s',
                            $miss['nomor'], $miss['want'] ?: '∅', $miss['got'] ?? '∅',
                            $miss['status'], $miss['confidence']
                        ));
                    }
                }
            }
        }
    }
}
