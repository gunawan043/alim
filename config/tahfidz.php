<?php

return [
    /*
     * Bobot NR Final mapel THF.
     *
     * Mapel tahfidz TIDAK lagi memakai bobot global: nilai diisi guru pada
     * buku halaqah, dan bobotnya disimpan per buku (`tahfidz_gradebooks`)
     * agar tiap halaqah bisa berbeda. Nilai di bawah hanya bobot awal saat
     * buku baru dibuat — lihat TahfidzGradebookService::DEFAULT_WEIGHTS.
     */
    'rapor_weights' => [
        'ziyadah' => 40.0,
        'rabt' => 20.0,
        'sts' => 20.0,
        'sas' => 20.0,
    ],

    /*
     * Ambang monitoring santri tertinggal pada halaman rekap Tahfidz.
     */
    /*
     * Setoran yang diinput guru langsung dianggap terverifikasi agar tidak
     * menambah beban approval. Matikan bila sekolah tetap ingin audit manual.
     */
    'auto_verify_setoran' => (bool) env('TAHFIDZ_AUTO_VERIFY_SETORAN', true),

    'monitoring' => [
        'inactive_setoran_days' => (int) env('TAHFIDZ_MONITORING_SETORAN_DAYS', 14),
        'inactive_mutabaah_days' => (int) env('TAHFIDZ_MONITORING_MUTABAAH_DAYS', 7),
        'low_target_percent' => (float) env('TAHFIDZ_MONITORING_TARGET_PERCENT', 50),
    ],
];
