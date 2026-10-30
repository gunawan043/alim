<?php

return [
    'consent_version' => env('ASRAMA_CONSENT_VERSION', '2026-10-v1'),

    // Wajibkan consent wali sebelum pengajuan izin/kunjungan baru.
    'consent_required' => env('ASRAMA_CONSENT_REQUIRED', true),

    // Maksimal hari izin meninggalkan pembelajaran (Pasal 14).
    'max_learning_leave_days' => (int) env('ASRAMA_MAX_LEARNING_LEAVE_DAYS', 3),

    'retention' => [
        // Data kunjungan yang lebih lama akan dianonimkan.
        'visit_months' => (int) env('ASRAMA_VISIT_RETENTION_MONTHS', 24),
        // Data izin yang lebih lama akan dianonimkan.
        'permit_months' => (int) env('ASRAMA_PERMIT_RETENTION_MONTHS', 36),
        // Audit scan QR dihapus setelah jumlah hari ini.
        'qr_scan_days' => (int) env('ASRAMA_QR_RETENTION_DAYS', 365),
    ],
];
