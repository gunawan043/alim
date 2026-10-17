<?php

/*
|--------------------------------------------------------------------------
| Evaluasi (Bank Soal, Kisi-Kisi, Paket Soal, Exam Engine, Item Analysis)
|--------------------------------------------------------------------------
|
| Configuration for the evaluation/assessment module. All thresholds
| drive SoalDedupService, BankSoalService, ItemAnalysisEngine, and the
| scoring pipeline. Adjust per school policy.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Similarity Thresholds (used by BankSoalService & SoalDedupService)
    |--------------------------------------------------------------------------
    |
    | These values govern duplicate detection during soal creation and bank
    | import. HARD_BLOCK rejects the operation entirely; SOFT_WARN records
    | a soft warning that the user can override.
    |
    | Jaccard similarity = |A ∩ B| / |A ∪ B| over normalized shingles.
    |
    */
    'similarity_threshold' => [
        'duplicate' => env('EVAL_SIM_DUPLICATE', 0.85),
        'warning' => env('EVAL_SIM_WARNING', 0.70),
        'ngram_size' => env('EVAL_NGRAM_SIZE', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Item Analysis Thresholds (used by ItemAnalysisEngine)
    |--------------------------------------------------------------------------
    |
    | Standard classical test theory (CTT) cut-offs:
    |   - Difficulty index (p):  proportion of students answering correctly
    |   - Discrimination index (D): top 27% − bottom 27% pass rate
    |   - Point-biserial correlation: item-total correlation (rpb)
    */
    'item_analysis' => [
        'difficulty' => [
            'easy' => 0.71,    // p >= 0.71  → mudah
            'medium' => 0.31,  // 0.31 <= p < 0.71 → sedang
            // p < 0.31 → sulit
        ],
        'discrimination' => [
            'good' => 0.40,    // D >= 0.40 → sangat diskriminatif
            'fair' => 0.20,    // 0.20 <= D < 0.40 → cukup
            // D < 0.20 → tidak diskriminatif
        ],
        'point_biserial' => [
            'min_acceptable' => 0.20, // rpb >= 0.20 → acceptable
        ],
        'high_group_pct' => 0.27, // top X% for discrimination
        'low_group_pct' => 0.27,  // bottom X% for discrimination
        'alpha_min_for_cron' => 10, // min students to run item analysis
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Cognitive & Difficulty Distributions (Kisi-Kisi Templates)
    |--------------------------------------------------------------------------
    */
    'kisi_kisi' => [
        'default_kognitif' => [
            'C1' => 0.10,  // mengingat
            'C2' => 0.25,  // memahami
            'C3' => 0.40,  // menerapkan
            'C4' => 0.20,  // menganalisis
            'C5' => 0.05,  // mengevaluasi
        ],
        'default_kesulitan' => [
            'mudah' => 0.30,
            'sedang' => 0.50,
            'sulit' => 0.20,
        ],
        'bobot_per_kognitif' => [
            'C1' => 1.0,
            'C2' => 1.5,
            'C3' => 2.0,
            'C4' => 2.5,
            'C5' => 3.0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Exam Attempt Defaults
    |--------------------------------------------------------------------------
    */
    'attempt' => [
        'default_duration_minutes' => 90,
        'max_duration_minutes' => 240,
        'default_kkm' => 70.0,
        'auto_submit_on_window_end' => true,
        'allow_pause' => false,
        'recalculate_skor_otomatis_on_resubmit' => false,
        'flag_suspicious_score_z' => 3.0, // z-score > 3.0 → flag
        'grace_period_minutes' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Paket Soal Defaults
    |--------------------------------------------------------------------------
    */
    'paket' => [
        'min_soal' => 5,
        'max_soal' => 100,
        'default_versi' => 1,
        'auto_publish' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cross-Teacher Cloning Policy
    |--------------------------------------------------------------------------
    |
    | When a teacher clones a soal from another teacher's bank, the source
    | bank must allow cloning AND the school's role policy must allow it.
    */
    'clone' => [
        'require_approval' => false,
        'log_to_clone_table' => true,
        'allow_industry_to_school' => false, // cannot clone from industry
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache (for Kisi-Kisi suggestions & item analysis results)
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'ttl_seconds' => 600, // 10 minutes
        'prefix' => 'eval',
    ],

];
