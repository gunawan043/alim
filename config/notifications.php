<?php

/**
 * Matriks channel notifikasi global (dapat diatur Super Admin).
 * Key event = nilai `action` pada NotificationUniversalService::send().
 */
return [
    'channels' => [
        'system' => ['label' => 'Sistem (in-app)', 'icon' => 'ri-notification-3-line'],
        'email' => ['label' => 'Email', 'icon' => 'ri-mail-line'],
        'wa' => ['label' => 'WhatsApp', 'icon' => 'ri-whatsapp-line'],
        'push' => ['label' => 'Push Mobile/Web', 'icon' => 'ri-notification-badge-line'],
    ],

    'events' => [
        // Kehadiran GTK
        'lesson_reminder' => ['group' => 'Kehadiran GTK', 'label' => 'Pengingat 5 menit sebelum jam pelajaran'],
        'teacher_late' => ['group' => 'Kehadiran GTK', 'label' => 'Guru terlambat masuk kelas'],

        // Perizinan & asrama
        'permit_approved' => ['group' => 'Perizinan & Asrama', 'label' => 'Izin disetujui'],
        'permit_rejected' => ['group' => 'Perizinan & Asrama', 'label' => 'Izin ditolak'],
        'permit_picked_up' => ['group' => 'Perizinan & Asrama', 'label' => 'Santri dijemput keluar asrama'],
        'permit_returned' => ['group' => 'Perizinan & Asrama', 'label' => 'Santri kembali ke asrama'],
        'overdue_permit' => ['group' => 'Perizinan & Asrama', 'label' => 'Izin melewati batas waktu'],
        'violation_recorded' => ['group' => 'Perizinan & Asrama', 'label' => 'Pelanggaran santri tercatat'],
        'reward_recorded' => ['group' => 'Perizinan & Asrama', 'label' => 'Penghargaan santri tercatat'],
        'visit_approved' => ['group' => 'Perizinan & Asrama', 'label' => 'Kunjungan disetujui'],
        'visit_rejected' => ['group' => 'Perizinan & Asrama', 'label' => 'Kunjungan ditolak'],
        'student_mutated_in' => ['group' => 'Perizinan & Asrama', 'label' => 'Santri mutasi masuk'],

        // Akademik
        'rapor' => ['group' => 'Akademik', 'label' => 'Rapor siap / nilai dikunci'],
        'rapor_published' => ['group' => 'Akademik', 'label' => 'Rapor dipublikasikan ke wali'],
        'nilai_published' => ['group' => 'Akademik', 'label' => 'Nilai mapel dipublikasikan ke wali'],
        'nilai_updated' => ['group' => 'Akademik', 'label' => 'Nilai mapel diperbarui untuk wali'],
        'activity_alpa' => ['group' => 'Asrama', 'label' => 'Santri tidak hadir pada kegiatan asrama'],
        'emergency_broadcast' => ['group' => 'Asrama', 'label' => 'Broadcast darurat asrama ke wali'],
        'dormitory_announcement' => ['group' => 'Asrama', 'label' => 'Pengumuman asrama ke wali'],
        'kaldik_created' => ['group' => 'Akademik', 'label' => 'Agenda Kalender Pendidikan dibuat'],
        'kaldik_updated' => ['group' => 'Akademik', 'label' => 'Agenda Kalender Pendidikan diperbarui'],

        // Sarpras & Unit Rumah Tangga (penugasan, perbaikan, pemeliharaan)
        'WorkOrderAssigned' => ['group' => 'Sarpras & Unit Rumah Tangga', 'label' => 'Work order ditugaskan ke petugas/teknisi'],
        'work_order_assigned' => ['group' => 'Sarpras & Unit Rumah Tangga', 'label' => 'Work order ditugaskan (varian lama)'],
        'WorkOrderStarted' => ['group' => 'Sarpras & Unit Rumah Tangga', 'label' => 'Work order mulai dikerjakan'],
        'WorkOrderCompleted' => ['group' => 'Sarpras & Unit Rumah Tangga', 'label' => 'Work order selesai'],
        'RepairRequestSubmitted' => ['group' => 'Sarpras & Unit Rumah Tangga', 'label' => 'Permintaan perbaikan diajukan'],
        'RepairStatusChanged' => ['group' => 'Sarpras & Unit Rumah Tangga', 'label' => 'Status perbaikan berubah'],
        'CleaningAssigned' => ['group' => 'Sarpras & Unit Rumah Tangga', 'label' => 'Petugas area kebersihan ditugaskan'],
        'MaintenanceDue' => ['group' => 'Sarpras & Unit Rumah Tangga', 'label' => 'Jadwal pemeliharaan mendekati jatuh tempo'],
        'MaintenanceOverdue' => ['group' => 'Sarpras & Unit Rumah Tangga', 'label' => 'Pemeliharaan melewati jatuh tempo'],
        'LoanOverdue' => ['group' => 'Sarpras & Unit Rumah Tangga', 'label' => 'Peminjaman barang terlambat dikembalikan'],
        'AssetMoved' => ['group' => 'Sarpras & Unit Rumah Tangga', 'label' => 'Aset dipindahkan'],
        'StockOpnameStarted' => ['group' => 'Sarpras & Unit Rumah Tangga', 'label' => 'Stock opname dimulai'],
        'StockOpnameCompleted' => ['group' => 'Sarpras & Unit Rumah Tangga', 'label' => 'Stock opname selesai'],
        'WarrantyExpired' => ['group' => 'Sarpras & Unit Rumah Tangga', 'label' => 'Garansi aset habis'],

        // Sistem
        'announcement' => ['group' => 'Sistem', 'label' => 'Pengumuman'],
        'login' => ['group' => 'Sistem', 'label' => 'Notifikasi login akun'],
        'password_forgot' => ['group' => 'Keamanan Akun', 'label' => 'Permintaan lupa password'],
        'password_reset' => ['group' => 'Keamanan Akun', 'label' => 'Password berhasil direset'],
        'password_changed' => ['group' => 'Keamanan Akun', 'label' => 'Password diubah dari profil'],
    ],
];
