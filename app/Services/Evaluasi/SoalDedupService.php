<?php

namespace App\Services\Evaluasi;

use App\Models\Soal;
use Illuminate\Support\Collection;

class SoalDedupService
{
    public const HARD_BLOCK_THRESHOLD = 0.85;

    public const SOFT_WARN_THRESHOLD = 0.70;

    public function __construct(
        protected ContentHashEngine $hashEngine,
    ) {}

    public function findExactDuplicate(string $contentHash, ?string $excludeSoalId = null): ?Soal
    {
        $query = Soal::where('content_hash', $contentHash);
        if ($excludeSoalId) {
            $query->where('id', '<>', $excludeSoalId);
        }

        return $query->withTrashed()->first();
    }

    public function findSimilarByShingles(string $subjectId, string $gradeLevelId, string $bankSoalId, array $candidateShingles, int $limit = 10, ?string $excludeSoalId = null): Collection
    {
        if (empty($candidateShingles)) {
            return collect();
        }

        $query = Soal::where('bank_soal_id', $bankSoalId)
            ->whereNotNull('shingles_hash')
            ->whereJsonContains('shingles_hash', $candidateShingles[0]);

        if ($excludeSoalId) {
            $query->where('id', '<>', $excludeSoalId);
        }

        $candidates = $query->limit(200)->get();

        $results = $candidates->map(function (Soal $soal) use ($candidateShingles) {
            $existing = $soal->shingles_hash ?? [];
            if (empty($existing)) {
                return null;
            }
            $similarity = $this->jaccardSimilarity($candidateShingles, $existing);

            return [
                'soal' => $soal,
                'similarity' => $similarity,
            ];
        })->filter()->sortByDesc('similarity')->take($limit)->values();

        return $results;
    }

    public function jaccardSimilarity(array $setA, array $setB): float
    {
        $a = array_unique($setA);
        $b = array_unique($setB);

        if (empty($a) && empty($b)) {
            return 1.0;
        }
        if (empty($a) || empty($b)) {
            return 0.0;
        }

        $intersection = count(array_intersect($a, $b));
        $union = count(array_unique(array_merge($a, $b)));

        return $union > 0 ? round($intersection / $union, 4) : 0.0;
    }

    public function jaccardSimilarityWith(array $setA, array $setB): float
    {
        return $this->jaccardSimilarity($setA, $setB);
    }

    public function classifySimilarity(float $jaccard): string
    {
        if ($jaccard >= self::HARD_BLOCK_THRESHOLD) {
            return 'block';
        }
        if ($jaccard >= self::SOFT_WARN_THRESHOLD) {
            return 'warn';
        }

        return 'ok';
    }
}
