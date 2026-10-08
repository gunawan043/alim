<?php

namespace App\Services\Evaluasi;

use App\Models\Soal;
use App\Models\SoalSimilarity;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pemeriksaan kemiripan soal lintas bank & lintas satuan pendidikan.
 *
 * Level:
 *  1. Exact Duplicate   — content_hash sama (100%)
 *  2. Text Similarity   — shingle/Jaccard sangat mirip (>= 70%)
 *  3. Semantic Similarity — struktur/substansi mirip walau redaksi berbeda (>= 60%),
 *     termasuk penguat kesamaan angka pada soal matematika.
 *
 * Hasil similarity adalah WARNING/quality control — bukan penolakan otomatis.
 */
class SoalSimilarityService
{
    public const WARN_THRESHOLD = 70.0;

    public const SEMANTIC_THRESHOLD = 60.0;

    /** Kata umum yang tidak membawa makna pembeda soal. */
    private const STOPWORDS = [
        'yang', 'dan', 'di', 'ke', 'dari', 'untuk', 'dengan', 'pada', 'adalah', 'itu', 'ini',
        'sebuah', 'atau', 'dalam', 'akan', 'tidak', 'bukan', 'berapa', 'berapakah', 'hasil',
        'hasilnya', 'nilai', 'hitunglah', 'tentukan', 'sebuah', 'adalah', 'the', 'a', 'an',
        'of', 'to', 'is', 'are', 'what', 'compute', 'calculate',
    ];

    public function __construct(
        private readonly ContentHashEngine $hashEngine,
        private readonly SoalDedupService $dedup,
    ) {}

    /**
     * Jalankan similarity check untuk satu soal (cross-bank/historical) & simpan hasilnya.
     *
     * @return array{summary: array<string, mixed>, results: Collection<int, array{soal: Soal, score: float, level: string}>}
     */
    public function check(Soal $soal, int $limit = 5, string $context = 'review'): array
    {
        $candidates = $this->candidates($soal);

        $shingles = $this->hashEngine->shinglesFromSoal($soal->pertanyaan ?? '');
        $tokens = $this->semanticTokens($soal->pertanyaan ?? '');
        $numbers = $this->numberTokens($soal->pertanyaan ?? '');

        $results = collect();

        foreach ($candidates as $candidate) {
            $level = null;
            $score = 0.0;

            if ($soal->content_hash && $candidate->content_hash === $soal->content_hash) {
                $level = SoalSimilarity::LEVEL_EXACT;
                $score = 100.0;
            } else {
                $textScore = $this->dedup->jaccardSimilarity($shingles, $candidate->shingles_hash ?? []) * 100;

                $semanticScore = $this->tokenSetSimilarity($tokens, $this->semanticTokens($candidate->pertanyaan ?? '')) * 100;
                if ($numbers !== [] && $numbers === $this->numberTokens($candidate->pertanyaan ?? '')) {
                    $semanticScore = min(100.0, $semanticScore + 15.0);
                }

                if ($textScore >= self::WARN_THRESHOLD) {
                    $level = SoalSimilarity::LEVEL_TEXT;
                    $score = round($textScore, 2);
                } elseif ($semanticScore >= self::SEMANTIC_THRESHOLD) {
                    $level = SoalSimilarity::LEVEL_SEMANTIC;
                    $score = round($semanticScore, 2);
                }
            }

            if ($level !== null) {
                $results->push(['soal' => $candidate, 'score' => $score, 'level' => $level]);
            }
        }

        $results = $results->sortByDesc('score')->take($limit)->values();

        $summary = $this->persist($soal, $results, $context);

        return ['summary' => $summary, 'results' => $results];
    }

    /**
     * Bandingkan dua soal secara langsung (untuk comparison view / paket).
     *
     * @return array{score: float, level: string}
     */
    public function compare(Soal $a, Soal $b): array
    {
        if ($a->content_hash && $b->content_hash === $a->content_hash && $a->id !== $b->id) {
            return ['score' => 100.0, 'level' => SoalSimilarity::LEVEL_EXACT];
        }

        $textScore = $this->dedup->jaccardSimilarity(
            $this->hashEngine->shinglesFromSoal($a->pertanyaan ?? ''),
            $this->hashEngine->shinglesFromSoal($b->pertanyaan ?? '')
        ) * 100;

        if ($textScore >= self::WARN_THRESHOLD) {
            return ['score' => round($textScore, 2), 'level' => SoalSimilarity::LEVEL_TEXT];
        }

        $semantic = $this->tokenSetSimilarity(
            $this->semanticTokens($a->pertanyaan ?? ''),
            $this->semanticTokens($b->pertanyaan ?? '')
        ) * 100;

        if ($semantic >= self::SEMANTIC_THRESHOLD) {
            return ['score' => round($semantic, 2), 'level' => SoalSimilarity::LEVEL_SEMANTIC];
        }

        return ['score' => round($textScore, 2), 'level' => SoalSimilarity::LEVEL_TEXT];
    }

    /**
     * Token kata bermakna (tanpa stopwords) untuk similarity level semantik.
     *
     * @return array<int, string>
     */
    public function semanticTokens(string $text): array
    {
        $normalized = $this->hashEngine->pertanyaanNormalized($text);

        if ($normalized === '') {
            return [];
        }

        $words = array_filter(explode(' ', $normalized), function ($word) {
            if ($word === '') {
                return false;
            }
            if (is_numeric($word)) {
                return true;
            }

            return ! in_array($word, self::STOPWORDS, true) && mb_strlen($word) > 2;
        });

        return array_values(array_unique($words));
    }

    /**
     * @return array<int, string>
     */
    public function numberTokens(string $text): array
    {
        preg_match_all('/\d+(?:[.,]\d+)?/', strip_tags($text), $matches);
        $numbers = array_values(array_unique($matches[0] ?? []));
        sort($numbers);

        return $numbers;
    }

    /**
     * @param  array<int, string>  $setA
     * @param  array<int, string>  $setB
     */
    public function tokenSetSimilarity(array $setA, array $setB): float
    {
        if ($setA === [] && $setB === []) {
            return 1.0;
        }
        if ($setA === [] || $setB === []) {
            return 0.0;
        }

        $intersection = count(array_intersect($setA, $setB));
        $union = count(array_unique(array_merge($setA, $setB)));

        return $union > 0 ? round($intersection / $union, 4) : 0.0;
    }

    /**
     * Kandidat pembanding: semua bank lintas satuan pendidikan dengan mapel sama
     * (repositori terpusat), bukan dibatasi school_id/bank_soal_id.
     */
    private function candidates(Soal $soal, int $limit = 500): Collection
    {
        $bank = $soal->bankSoal;

        // Mapel serumpun lintas satuan dapat memakai baris subject berbeda → cocokkan id + nama.
        $subjectIds = [];
        if ($bank?->subject_id) {
            $subject = Subject::find($bank->subject_id);
            $subjectIds = $subject
                ? Subject::query()->where('id', $subject->id)->orWhere('name', $subject->name)->pluck('id')->all()
                : [$bank->subject_id];
        }

        return Soal::query()
            ->with(['bankSoal:id,school_id,subject_id,jenjang,grade_level_id,academic_year_id,semester,nama'])
            ->where('id', '<>', $soal->id)
            ->whereNotNull('pertanyaan')
            ->when($subjectIds !== [], fn ($q) => $q->whereHas('bankSoal', fn ($q2) => $q2->whereIn('subject_id', $subjectIds)))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  Collection<int, array{soal: Soal, score: float, level: string}>  $results
     * @return array<string, mixed>
     */
    private function persist(Soal $soal, Collection $results, string $context): array
    {
        return DB::transaction(function () use ($soal, $results, $context) {
            SoalSimilarity::query()
                ->where('soal_id', $soal->id)
                ->where('context', $context)
                ->delete();

            foreach ($results as $result) {
                SoalSimilarity::create([
                    'soal_id' => $soal->id,
                    'compared_soal_id' => $result['soal']->id,
                    'score' => $result['score'],
                    'level' => $result['level'],
                    'context' => $context,
                    'checked_at' => now(),
                ]);
            }

            $summary = [
                'checked_at' => now()->toIso8601String(),
                'total' => $results->count(),
                'highest' => (float) ($results->max('score') ?? 0),
                'exact' => $results->where('level', SoalSimilarity::LEVEL_EXACT)->count(),
                'text' => $results->where('level', SoalSimilarity::LEVEL_TEXT)->count(),
                'semantic' => $results->where('level', SoalSimilarity::LEVEL_SEMANTIC)->count(),
            ];

            $soal->forceFill([
                'similarity_checked_at' => now(),
                'similarity_summary' => $summary,
            ])->save();

            return $summary;
        });
    }
}
