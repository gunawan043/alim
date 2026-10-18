<?php

return [
    /*
     * Bobot nilai rapor mapel THF (Tahfidzul Qur'an).
     * Nilai setoran berasal dari rata-rata setoran terverifikasi.
     * Bobot dinormalisasi otomatis bila salah satu komponen belum ada.
     */
    'rapor_weights' => [
        'setoran' => (float) env('TAHFIDZ_RAPOR_SETORAN_WEIGHT', 0.60),
        'sts' => (float) env('TAHFIDZ_RAPOR_STS_WEIGHT', 0.20),
        'sas' => (float) env('TAHFIDZ_RAPOR_SAS_WEIGHT', 0.20),
    ],

    /*
     * Ambang monitoring santri tertinggal pada halaman rekap Tahfidz.
     */
    'monitoring' => [
        'inactive_setoran_days' => (int) env('TAHFIDZ_MONITORING_SETORAN_DAYS', 14),
        'inactive_mutabaah_days' => (int) env('TAHFIDZ_MONITORING_MUTABAAH_DAYS', 7),
        'low_target_percent' => (float) env('TAHFIDZ_MONITORING_TARGET_PERCENT', 50),
    ],
];
