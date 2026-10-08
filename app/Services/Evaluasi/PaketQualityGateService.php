<?php

namespace App\Services\Evaluasi;

use App\Models\PaketSoal;
use Illuminate\Support\Collection;

/**
 * Quality gate paket soal sebelum approval:
 *  - duplikasi internal antar soal dalam paket;
 *  - kemiripan historis dengan soal di luar paket (lintas tahun/bank/satuan).
 *
 * Hasilnya warning untuk reviewer — bukan penolakan otomatis.
 */
class PaketQualityGateService
{
    public function __construct(private readonly SoalSimilarityService $similarity) {}

    /**
     * @return array{internal: array<int, array<string, mixed>>, historical: array<int, array<string, mixed>>, summary: array<string, mixed>}
     */
    public function run(PaketSoal $paket): array
    {
        $paket->loadMissing(['items.soal.options', 'items.soal.bankSoal']);

        $soals = $paket->items->map->soal->filter()->values();
        $paketSoalIds = $soals->pluck('id')->all();

        // ── Duplikasi internal
        $internal = [];
        foreach ($soals as $i => $a) {
            foreach ($soals->slice($i + 1) as $b) {
                $comparison = $this->similarity->compare($a, $b);

                if ($comparison['score'] >= SoalSimilarityService::SEMANTIC_THRESHOLD) {
                    $internal[] = [
                        'a' => $a,
                        'b' => $b,
                        'score' => $comparison['score'],
                        'level' => $comparison['level'],
                    ];
                }
            }
        }

        // ── Kemiripan historis (soal di luar paket)
        $historical = [];
        foreach ($soals as $soal) {
            $result = $this->similarity->check($soal, 5, 'package');

            foreach ($result['results'] as $row) {
                if (in_array($row['soal']->id, $paketSoalIds, true)) {
                    continue;
                }

                $historical[] = [
                    'soal' => $soal,
                    'compared' => $row['soal'],
                    'score' => $row['score'],
                    'level' => $row['level'],
                ];
            }
        }

        $historical = collect($historical)
            ->sortByDesc('score')
            ->unique(fn ($row) => $row['compared']->id)
            ->take(10)
            ->values()
            ->all();

        $summary = [
            'checked_at' => now()->toIso8601String(),
            'internal_duplicates' => count($internal),
            'historical_warnings' => count($historical),
            'highest_historical' => (float) (collect($historical)->max('score') ?? 0),
        ];

        $paket->forceFill([
            'similarity_checked_at' => now(),
            'similarity_summary' => $summary,
        ])->save();

        return ['internal' => $internal, 'historical' => $historical, 'summary' => $summary];
    }

    /**
     * Ringkas untuk tampilan badge.
     *
     * @return array<string, mixed>
     */
    public function summary(PaketSoal $paket): array
    {
        return $paket->similarity_summary ?? [
            'checked_at' => null,
            'internal_duplicates' => 0,
            'historical_warnings' => 0,
            'highest_historical' => 0,
        ];
    }
}
