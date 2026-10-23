<?php

return [
    // Reset akumulasi poin. Pilihan: yearly / semester / never.
    // Poin permanen (is_permanent) tidak ikut reset.
    'reset_period' => env('ASRAMA_POINT_RESET_PERIOD', 'yearly'),

    // Reward selalu menambah poin positif. Opsi offset disimpan untuk
    // kebijakan masa depan; default tidak mengurangi poin pelanggaran.
    'reward_can_offset_violation' => (bool) env('ASRAMA_REWARD_CAN_OFFSET', false),

    // Batas reward sikap per semester.
    'max_behavior_reward_per_semester' => (int) env('ASRAMA_MAX_BEHAVIOR_REWARD', 25),

    // Ambang tindakan. Dapat diganti menjadi tabel pengaturan saat UI
    // master data diterapkan. Tindakan berat wajib melalui approval.
    'thresholds' => [
        ['min' => 1, 'max' => 14, 'action' => 'Pembinaan ringan', 'requires_approval' => false],
        ['min' => 15, 'max' => 49, 'action' => 'Panggilan wali / pembinaan sedang', 'requires_approval' => false],
        ['min' => 50, 'max' => 100, 'action' => 'Surat peringatan / rekomendasi skorsing', 'requires_approval' => true],
        ['min' => 101, 'max' => null, 'action' => 'Rekomendasi dikeluarkan', 'requires_approval' => true],
    ],
];
