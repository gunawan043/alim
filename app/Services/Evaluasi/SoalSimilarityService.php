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
 * Level (semuanya berbasis teks terukur — bukan AI/semantic embedding):
 *  1. Exact Duplicate  — content_hash sama setelah normalisasi (100%)
 *  2. Text Similarity  — kemiripan karakter 3-gram/Jaccard (>= 70%)
 *  3. Token Overlap    — kesamaan kata bermakna (bag-of-words Jaccard >= 60%),
 *     dengan penguat bila angka-angka soal identik.
 *
 * Hasil similarity adalah WARNING/quality control — bukan penolakan otomatis.
 */
class SoalSimilarityService
{
    public const WARN_THRESHOLD = 70.0;

    public const TOKEN_THRESHOLD = 60.0;

    /** @deprecated gunakan TOKEN_THRESHOLD */
    public const SEMANTIC_THRESHOLD = self::TOKEN_THRESHOLD;

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
        $shingles = $this->hashEngine->shinglesFromSoal($soal->pertanyaan ?? '');

        $candidates = $this->candidates($soal, $shingles);
        $tokens = $this->significantTokens($soal->pertanyaan ?? '');
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

                $tokenScore = $this->tokenSetSimilarity($tokens, $this->significantTokens($candidate->pertanyaan ?? '')) * 100;
                if ($numbers !== [] && $numbers === $this->numberTokens($candidate->pertanyaan ?? '')) {
                    $tokenScore = min(100.0, $tokenScore + 15.0);
                }

                if ($textScore >= self::WARN_THRESHOLD) {
                    $level = SoalSimilarity::LEVEL_TEXT;
                    $score = round($textScore, 2);
                } elseif ($tokenScore >= self::TOKEN_THRESHOLD) {
                    $level = SoalSimilarity::LEVEL_TOKEN;
                    $score = round($tokenScore, 2);
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

        $tokenScore = $this->tokenSetSimilarity(
            $this->significantTokens($a->pertanyaan ?? ''),
            $this->significantTokens($b->pertanyaan ?? '')
        ) * 100;

        if ($tokenScore >= self::TOKEN_THRESHOLD) {
            return ['score' => round($tokenScore, 2), 'level' => SoalSimilarity::LEVEL_TOKEN];
        }

        return ['score' => round($textScore, 2), 'level' => SoalSimilarity::LEVEL_DIFFERENT];
    }

    /**
     * Token kata bermakna (tanpa stopwords) untuk level Token Overlap.
     * Berbasis leksikal (bag-of-words), bukan embedding semantik.
     *
     * @return array<int, string>
     */
    public function significantTokens(string $text): array
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
     * (repositori terpusat), difilter via index (hash/tp/materi/shingle) agar
     * tidak memindai seluruh tabel saat data sudah besar.
     *
     * @param  array<int, string>  $candidateShingles
     */
    private function candidates(Soal $soal, array $candidateShingles, int $limit = 200): Collection
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

        // Probe shingle: gunakan beberapa shingle teratas sebagai index pencarian.
        $probes = array_slice($candidateShingles, 0, 3);

        $hasPrefilter = $soal->content_hash || $soal->tp_id || $soal->materi || $probes !== [];

        $query = Soal::query()
            ->with(['bankSoal:id,school_id,subject_id,jenjang,grade_level_id,academic_year_id,semester,nama,jenis_soal'])
            ->whereKeyNot($soal->id)
            ->whereNotNull('pertanyaan')
            ->when($subjectIds !== [], fn ($q) => $q->whereHas('bankSoal', fn ($q2) => $q2->whereIn('subject_id', $subjectIds)));

        if ($hasPrefilter) {
            $query->where(function ($q) use ($soal, $probes) {
                if ($soal->content_hash) {
                    $q->orWhere('content_hash', $soal->content_hash);
                }
                if ($soal->tp_id) {
                    $q->orWhere('tp_id', $soal->tp_id);
                }
                if ($soal->materi) {
                    $q->orWhere('materi', $soal->materi);
                }
                foreach ($probes as $shingle) {
                    $q->orWhereJsonContains('shingles_hash', $shingle);
                }
            });
        }

        return $query->orderByDesc('created_at')->limit($limit)->get();
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
                'token' => $results->where('level', SoalSimilarity::LEVEL_TOKEN)->count(),
            ];

            $soal->forceFill([
                'similarity_checked_at' => now(),
                'similarity_summary' => $summary,
            ])->save();

            return $summary;
        });
    }
}
