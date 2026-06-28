<?php

/**
 * F9 — Sumatif Evaluation Ecosystem Configuration
 *
 * Phase 1 (Foundation) config:
 *  - tujuan_pembelajaran: sample TP per subject/grade/fase. Seeder only inserts
 *    rows whose subject_code and grade_level exist in DB. Adjust freely.
 *  - dedup thresholds: 0.85 = block, 0.70 = warn, else OK.
 *  - min_attempts_for_analysis: item analysis only runs when N >= this.
 */

return [
    'dedup' => [
        'block_threshold' => env('F9_DEDUP_BLOCK', 0.85),
        'warn_threshold' => env('F9_DEDUP_WARN', 0.70),
        'shingle_size' => env('F9_SHINGLE_SIZE', 3),
    ],

    'analysis' => [
        'min_attempts' => env('F9_MIN_ATTEMPTS', 10),
        'upper_lower_percentile' => 0.27,
    ],

    'tujuan_pembelajaran' => [
        // Matematika Fase E (SMA Kelas X) — only the subject that exists in DB
        ['subject_code' => 'MTK', 'grade_level' => 10, 'academic_year_name' => '2026/2027',
            'semester' => 'ganjil', 'fase' => 'E', 'kode_tp' => 'MTK.E.10.1',
            'elemen' => 'Aljabar',
            'deskripsi' => 'Peserta didik mampu menyelesaikan persamaan dan pertidaksamaan linear satu variabel.',
            'alokasi_waktu' => 8, 'urutan' => 1, 'created_by_email' => 'admin@alim.local'],

        ['subject_code' => 'MTK', 'grade_level' => 10, 'academic_year_name' => '2026/2027',
            'semester' => 'ganjil', 'fase' => 'E', 'kode_tp' => 'MTK.E.10.2',
            'elemen' => 'Aljabar',
            'deskripsi' => 'Peserta didik mampu menganalisis fungsi kuadrat dan grafiknya.',
            'alokasi_waktu' => 10, 'urutan' => 2, 'created_by_email' => 'admin@alim.local'],

        ['subject_code' => 'MTK', 'grade_level' => 10, 'academic_year_name' => '2026/2027',
            'semester' => 'ganjil', 'fase' => 'E', 'kode_tp' => 'MTK.E.10.3',
            'elemen' => 'Geometri',
            'deskripsi' => 'Peserta didik mampu menghitung jarak dan sudut pada bidang datar.',
            'alokasi_waktu' => 12, 'urutan' => 3, 'created_by_email' => 'admin@alim.local'],
    ],
];
