<?php

namespace App\Console\Commands;

use App\Models\Soal;
use App\Services\Evaluasi\BloomTaxonomyService;
use Illuminate\Console\Command;

class ClassifyBloomLevels extends Command
{
    protected $signature = 'soal:classify-bloom
                            {--refresh : Hitung ulang semua soal, termasuk yang sudah punya level}
                            {--chunk=100 : Jumlah soal per batch}';

    protected $description = 'Klasifikasikan level kognitif Bloom (C1-C6 / L1-L3) pada bank soal.';

    public function handle(): int
    {
        $chunk = max(10, (int) $this->option('chunk'));
        $refresh = (bool) $this->option('refresh');
        $query = Soal::query()->with('tujuanPembelajaran:id,deskripsi');

        if (! $refresh) {
            $query->where(function ($q) {
                $q->whereNull('level_kognitif')->orWhereNull('level_kognitif_rumpun');
            });
        }

        $total = (clone $query)->count();
        $this->info(($refresh ? 'Menghitung ulang' : 'Melengkapi')." level Bloom untuk {$total} soal...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;
        $query->chunkById($chunk, function ($soals) use (&$updated, $bar) {
            foreach ($soals as $soal) {
                try {
                    $result = app(BloomTaxonomyService::class)
                        ->classify($soal->pertanyaan, $soal->tujuanPembelajaran?->deskripsi, $soal->materi);

                    $soal->forceFill([
                        'level_kognitif' => $result['level'],
                        'level_kognitif_rumpun' => $result['rumpun'],
                        'bloom_confidence' => $result['confidence'],
                        'bloom_classified_at' => now(),
                    ])->saveQuietly();

                    $updated++;
                } catch (\Throwable $e) {
                    $this->newLine();
                    $this->warn("Soal {$soal->id} gagal: {$e->getMessage()}");
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Selesai. {$updated} soal diperbarui.");

        return self::SUCCESS;
    }
}
