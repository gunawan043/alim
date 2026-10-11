<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Legenda Kaldik — warna default per jenis
    |--------------------------------------------------------------------------
    | Dipakai untuk pre-select warna pada form Kaldik & server-side default
    | ketika warna tidak dipilih. Dapat ditimpa lewat settings (key
    | `kaldik_legend_{type}`), memakai Kaldik::TYPE_* sebagai kunci.
    */
    'legend' => [
        'libur' => ['label' => 'Libur (Nasional/Semester)', 'color' => '#EF4444'],
        'ujian' => ['label' => 'Ujian / Sumatif', 'color' => '#F59E0B'],
        'kegiatan' => ['label' => 'Kegiatan Sekolah', 'color' => '#3B82F6'],
        'hari_efektif' => ['label' => 'Hari Efektif', 'color' => '#10B981'],
        'tahunan' => ['label' => 'Agenda Tahunan', 'color' => '#8B5CF6'],
        'mid_semester' => ['label' => 'Mid Semester', 'color' => '#06B6D4'],
        'program_bersama' => ['label' => 'Program Bersama', 'color' => '#84CC16'],
        'lainnya' => ['label' => 'Lainnya', 'color' => '#64748B'],
    ],
];
