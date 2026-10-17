<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ljk\LjkSyntheticRenderer;
use App\Services\Ljk\LjkTemplateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Bangun dataset LJK sintetis berlabel untuk kalibrasi mesin OMR.
 *
 * Skenario mencakup: pulpen hitam/biru, X tipis/tebal/miring/tidak simetris,
 * menyentuh garis kotak, kosong, ganda, centang, garis miring, coretan,
 * bekas penghapusan, foto miring, perspektif, gelap, dan bayangan.
 */
class LjkGenerateDatasetCommand extends Command
{
    protected $signature = 'ljk:generate-dataset
        {--count=40 : Jumlah lembar}
        {--questions=20 : Jumlah soal per lembar}
        {--options=4 : Jumlah opsi per soal}
        {--out=storage/app/ljk/datasets/demo : Direktori keluaran}
        {--seed=20261015 : Seed acak deterministik}';

    protected $description = 'Hasilkan dataset LJK sintetis berlabel (JSON + JPEG) untuk kalibrasi OMR';

    public function handle(): int
    {
        // Mesin OMR memproses citra di memori; pastikan ruang cukup.
        if ((int) ini_get('memory_limit') !== -1 && (int) ini_get('memory_limit') < 512) {
            ini_set('memory_limit', '512M');
        }

        $count = max(1, (int) $this->option('count'));
        $questions = max(5, (int) $this->option('questions'));
        $options = max(2, min(5, (int) $this->option('options')));
        $seed = (int) $this->option('seed');
        $out = base_path((string) $this->option('out'));

        mt_srand($seed);

        File::ensureDirectoryExists($out);

        $specs = [];
        for ($i = 1; $i <= $questions; $i++) {
            $specs[] = ['nomor' => $i, 'jenis' => 'pg', 'options' => $options];
        }

        $templateService = app(LjkTemplateService::class);
        $geometry = $templateService->buildGeometry($specs);
        $renderer = app(LjkSyntheticRenderer::class);

        $scenarios = $this->scenarios();
        $labels = [];

        for ($i = 1; $i <= $count; $i++) {
            $scenario = $scenarios[($i - 1) % count($scenarios)];
            $answers = [];
            $slots = [];
            $styles = [];

            for ($n = 1; $n <= $questions; $n++) {
                $slot = chr(65 + mt_rand(0, $options - 1));
                $answers[$n] = $slot;
                $slots[$n] = $slot;
            }

            // Variasi tanda pada beberapa nomor.
            $styles[mt_rand(1, $questions)] = 'x_blue';
            $styles[mt_rand(1, $questions)] = 'thin';
            $styles[mt_rand(1, $questions)] = 'asymmetric';

            if ($scenario['extra']) {
                $target = mt_rand(1, $questions);
                $answers[$target] = '';
                $slots[$target] = '';
                $styles[$target] = 'empty';

                // Ganda = dua opsi berbeda ditandai.
                $target = mt_rand(1, $questions);
                $a = chr(65 + mt_rand(0, $options - 1));
                do {
                    $b = chr(65 + mt_rand(0, $options - 1));
                } while ($b === $a);
                $answers[$target] = $a.$b;
                $slots[$target] = $a.$b;
                $styles[$target] = 'double';
            }

            // Nomor dengan tanda di luar format: label '?' = harus masuk
            // pemeriksaan guru; kotak tetap diisi dengan tanda tidak wajar.
            if ($scenario['odd_marks']) {
                $target = mt_rand(1, $questions);
                $answers[$target] = '?';
                $slots[$target] = chr(65 + mt_rand(0, $options - 1));
                $styles[$target] = ['check', 'slash', 'scribble', 'erase', 'dot'][mt_rand(0, 4)];
            }

            $file = sprintf('sheet-%04d-%s.jpg', $i, $scenario['key']);

            $renderer->renderToFile($geometry, ['answers' => $slots, 'styles' => $styles], [
                'path' => $out.'/'.$file,
                'participant_seq' => $i,
                'rotation_deg' => $scenario['rotation'],
                'perspective' => $scenario['perspective'],
                'shadow' => $scenario['shadow'],
                'brightness' => $scenario['brightness'],
                'blur' => $scenario['blur'],
                'canvas_scale' => $scenario['canvas'],
                'jpeg_quality' => $scenario['quality'],
            ]);

            $labels[] = [
                'file' => $file,
                'scenario' => $scenario['key'],
                'participant_seq' => $i,
                'answers' => array_map('strval', $answers),
                'styles' => $styles,
            ];

            $this->output->write('.');
        }

        $this->newLine();

        File::put($out.'/labels.json', json_encode([
            'generated_at' => now()->toIso8601String(),
            'seed' => $seed,
            'engine_version' => config('ljk.engine_version'),
            'questions' => $questions,
            'options' => $options,
            'images' => $labels,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("Dataset: {$count} lembar di {$out}");
        $this->line('Label: '.$out.'/labels.json');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function scenarios(): array
    {
        return [
            ['key' => 'bersih', 'extra' => false, 'odd_marks' => false, 'rotation' => 0.0, 'perspective' => 0.0, 'shadow' => 0.0, 'brightness' => 0, 'blur' => 0, 'canvas' => 1.2, 'quality' => 88],
            ['key' => 'miring', 'extra' => true, 'odd_marks' => false, 'rotation' => 4.5, 'perspective' => 0.07, 'shadow' => 0.12, 'brightness' => -6, 'blur' => 0, 'canvas' => 1.6, 'quality' => 86],
            ['key' => 'bayangan', 'extra' => true, 'odd_marks' => false, 'rotation' => 2.0, 'perspective' => 0.05, 'shadow' => 0.32, 'brightness' => -14, 'blur' => 0, 'canvas' => 1.45, 'quality' => 84],
            ['key' => 'blur-ringan', 'extra' => true, 'odd_marks' => true, 'rotation' => 1.5, 'perspective' => 0.03, 'shadow' => 0.10, 'brightness' => -4, 'blur' => 1, 'canvas' => 1.4, 'quality' => 80],
            ['key' => 'gelap', 'extra' => true, 'odd_marks' => false, 'rotation' => 3.0, 'perspective' => 0.04, 'shadow' => 0.20, 'brightness' => -24, 'blur' => 0, 'canvas' => 1.5, 'quality' => 82],
            ['key' => 'tanda-luar-format', 'extra' => false, 'odd_marks' => true, 'rotation' => 0.5, 'perspective' => 0.0, 'shadow' => 0.05, 'brightness' => 0, 'blur' => 0, 'canvas' => 1.2, 'quality' => 88],
        ];
    }
}
