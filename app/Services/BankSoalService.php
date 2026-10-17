<?php

namespace App\Services;

use App\Models\Soal;
use Illuminate\Support\Facades\Validator;

/**
 * BankSoalService — duplicate detection & soal content validation.
 *
 * NOTE: Duplication is checked at the SOAL level (not bank level). Banks
 * are just containers; the actual content lives in `soal.pertanyaan`.
 *
 * The legacy version of this service incorrectly queried BankSoal for
 * `content_hash`/`judul` columns that exist on Soal, not BankSoal. This
 * implementation routes to the correct model.
 */
class BankSoalService
{
    public const DEFAULT_NGRAM = 5;

    public const HARD_BLOCK = 'hard_block';

    public const SOFT_WARN = 'soft_warn';

    public const UNIQUE = 'unique';

    /**
     * Normalize pertanyaan text and produce a SHA-256 content hash.
     * Whitespace and HTML differences should not produce a different hash
     * for semantically identical content.
     */
    public static function contentHash(string $pertanyaan): string
    {
        $normalized = strtolower(preg_replace(
            '/[\s\x{200B}-\x{200D}\x{FEFF}]+/u',
            ' ',
            trim(strip_tags($pertanyaan))
        ));

        return hash('sha256', $normalized);
    }

    /**
     * Generate shingles (character n-grams) from text. Returns unique array.
     */
    public static function toShingles(string $text, int $n = self::DEFAULT_NGRAM): array
    {
        $normalized = strtolower(preg_replace('/\s+/', ' ', trim($text)));
        if (mb_strlen($normalized) < $n) {
            return $normalized === '' ? [] : [$normalized];
        }
        $shingles = [];
        for ($i = 0; $i <= mb_strlen($normalized) - $n; $i++) {
            $shingles[] = mb_substr($normalized, $i, $n);
        }

        return array_values(array_unique($shingles));
    }

    /**
     * Jaccard similarity of two text strings via character n-grams.
     */
    public static function jaccardSimilarity(string $textA, string $textB, int $n = self::DEFAULT_NGRAM): float
    {
        return self::jaccardBetweenSets(
            self::toShingles($textA, $n),
            self::toShingles($textB, $n)
        );
    }

    /**
     * Jaccard similarity of two pre-computed shingle sets.
     */
    public static function jaccardBetweenSets(array $setA, array $setB): float
    {
        if (empty($setA) && empty($setB)) {
            return 1.0;
        }
        if (empty($setA) || empty($setB)) {
            return 0.0;
        }

        $intersection = count(array_intersect($setA, $setB));
        $union = count(array_unique([...$setA, ...$setB]));

        return $union > 0 ? round($intersection / $union, 4) : 0.0;
    }

    /**
     * Determine if a new soal is a duplicate of an existing approved soal
     * within the same (school_id, subject_id) scope.
     *
     * Returns a verdict object: ['status' => 'hard_block'|'soft_warn'|'unique',
     *                              'best_match' => Soal|null,
     *                              'similarity' => float]
     */
    public static function checkDuplicate(
        string $schoolId,
        string $subjectId,
        string $pertanyaan,
        ?string $excludeSoalId = null
    ): array {
        $hash = self::contentHash($pertanyaan);

        // 1) Exact content_hash match → hard block
        $exact = Soal::where('bank_soal_id', '!=', null)
            ->whereHas('bankSoal', function ($q) use ($schoolId, $subjectId) {
                $q->where('school_id', $schoolId)
                    ->where('subject_id', $subjectId);
            })
            ->where('content_hash', $hash)
            ->when($excludeSoalId, fn ($q, $id) => $q->where('id', '<>', $id))
            ->first();

        if ($exact) {
            return [
                'status' => self::HARD_BLOCK,
                'best_match' => $exact,
                'similarity' => 1.0,
                'reason' => 'Pertanyaan persis sama dengan soal yang sudah ada.',
            ];
        }

        // 2) Fuzzy match via Jaccard shingles
        $newShingles = self::toShingles($pertanyaan);
        $bestSim = 0.0;
        $bestMatch = null;

        $threshold = config('eval.similarity_threshold.warning', 0.70);

        Soal::where('bank_soal_id', '!=', null)
            ->whereHas('bankSoal', function ($q) use ($schoolId, $subjectId) {
                $q->where('school_id', $schoolId)
                    ->where('subject_id', $subjectId);
            })
            ->where('status', 'approved')
            ->when($excludeSoalId, fn ($q, $id) => $q->where('id', '<>', $id))
            ->whereNotNull('shingles_hash')
            ->select(['id', 'pertanyaan', 'shingles_hash', 'times_used'])
            ->chunk(200, function ($soals) use (&$bestSim, &$bestMatch, $newShingles) {
                foreach ($soals as $soal) {
                    $candidate = is_array($soal->shingles_hash)
                        ? $soal->shingles_hash
                        : (json_decode($soal->shingles_hash, true) ?: []);
                    $sim = self::jaccardBetweenSets($newShingles, $candidate);
                    if ($sim > $bestSim) {
                        $bestSim = $sim;
                        $bestMatch = $soal;
                    }
                }
            });

        $hardBlock = (float) config('eval.similarity_threshold.duplicate', 0.85);

        if ($bestSim >= $hardBlock) {
            return [
                'status' => self::HARD_BLOCK,
                'best_match' => $bestMatch,
                'similarity' => $bestSim,
                'reason' => "Tingkat kemiripan soal baru ({$bestSim}) di atas ambang hard-block ({$hardBlock}).",
            ];
        }

        if ($bestSim >= $threshold) {
            return [
                'status' => self::SOFT_WARN,
                'best_match' => $bestMatch,
                'similarity' => $bestSim,
                'reason' => "Soal baru mirip ({$bestSim}) dengan soal yang sudah ada. Periksa apakah benar-benar perlu dibuat ulang.",
            ];
        }

        return [
            'status' => self::UNIQUE,
            'best_match' => null,
            'similarity' => $bestSim,
            'reason' => null,
        ];
    }

    /**
     * Backward-compatible boolean: true if the new soal is a duplicate.
     */
    public static function isDuplicate(
        string $schoolId,
        string $subjectId,
        string $pertanyaan,
        ?string $excludeSoalId = null
    ): bool {
        $verdict = self::checkDuplicate($schoolId, $subjectId, $pertanyaan, $excludeSoalId);

        return $verdict['status'] === self::HARD_BLOCK;
    }

    /**
     * Find all near-duplicates of a given pertanyaan, sorted by similarity desc.
     *
     * @return array<int, array{soal_id:string, snippet:string, similarity:float, times_used:int, is_probably_duplicate:bool}>
     */
    public static function findDuplicates(
        string $schoolId,
        string $subjectId,
        string $pertanyaan,
        ?float $threshold = null
    ): array {
        $threshold ??= (float) config('eval.similarity_threshold.warning', 0.70);
        $hardBlock = (float) config('eval.similarity_threshold.duplicate', 0.85);

        $newShingles = self::toShingles($pertanyaan);
        $results = [];

        Soal::where('bank_soal_id', '!=', null)
            ->whereHas('bankSoal', function ($q) use ($schoolId, $subjectId) {
                $q->where('school_id', $schoolId)
                    ->where('subject_id', $subjectId);
            })
            ->where('status', 'approved')
            ->whereNotNull('shingles_hash')
            ->select(['id', 'pertanyaan', 'shingles_hash', 'times_used'])
            ->chunk(200, function ($soals) use (&$results, $newShingles, $threshold, $hardBlock) {
                foreach ($soals as $soal) {
                    $candidate = is_array($soal->shingles_hash)
                        ? $soal->shingles_hash
                        : (json_decode($soal->shingles_hash, true) ?: []);
                    $sim = self::jaccardBetweenSets($newShingles, $candidate);
                    if ($sim >= $threshold) {
                        $results[] = [
                            'soal_id' => $soal->id,
                            'snippet' => mb_substr(strip_tags($soal->pertanyaan), 0, 120),
                            'similarity' => round($sim * 100, 2),
                            'times_used' => (int) $soal->times_used,
                            'is_probably_duplicate' => $sim >= $hardBlock,
                        ];
                    }
                }
            });

        usort($results, fn ($a, $b) => $b['similarity'] <=> $a['similarity']);

        return $results;
    }

    /**
     * Validate a soal payload against the current schema.
     */
    public static function validateSoal(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, [
            'bank_soal_id' => 'required|exists:bank_soal,id',
            'tp_id' => 'nullable|exists:tujuan_pembelajaran,id',
            'tipe_soal' => 'required|in:pg,bs,jodoh,isian,uraian',
            'pertanyaan' => 'required|string|min:10|max:10000',
            'bobot_default' => 'required|numeric|min:0|max:100',
            'tingkat_kesulitan_estimasi' => 'nullable|in:mudah,sedang,sulit',
            'waktu_estimasi_menit' => 'nullable|integer|min:1|max:30',
            'options' => 'required_if:tipe_soal,pg,bs|array|min:2|max:6',
            'options.*.label' => 'required_with:options|string|max:5',
            'options.*.teks_opsi' => 'required_with:options|string|max:2000',
            'options.*.is_correct' => 'required_with:options|boolean',
            'tags' => 'nullable|array|max:20',
            'gambar_path' => 'nullable|string|max:500',
            'audio_path' => 'nullable|string|max:500',
            'created_by' => 'nullable|exists:users,id',
        ], [
            'pertanyaan.min' => 'Soal minimal 10 karakter.',
            'options.required_if' => 'Soal PG/BS membutuhkan minimal 2 opsi jawaban.',
            'options.min' => 'Minimal 2 opsi jawaban.',
            'options.max' => 'Maksimal 6 opsi jawaban (A-F).',
        ]);
    }
}
