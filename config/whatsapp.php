<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Gateway (alternatif tanpa API resmi)
    |--------------------------------------------------------------------------
    | Provider: fonnte | wablas | generic | log (GRATIS/manual via wa.me).
    |
    | Mode penerima:
    |  - central  : semua notifikasi terpusat ke SATU nomor admin ALIM
    |  - guardian : ke nomor wali santri (mahrom)
    |  - both     : keduanya
    */
    'enabled' => env('WA_ENABLED', true),

    'provider' => env('WA_PROVIDER', 'log'),

    'token' => env('WA_API_TOKEN'),

    'url' => env('WA_API_URL'),

    'sender' => env('WA_SENDER'),

    'mode' => env('WA_MODE', 'central'),

    'central_number' => env('WA_CENTRAL_NUMBER'),

    'delivery_mode' => env('WA_DELIVERY_MODE', 'central'),

    /*
    | Footer branding yang ditambahkan ke akhir pesan (opsional).
    | Ini BUKAN penghapus watermark provider; watermark Fonnte hanya
    | hilang pada paket berbayar. Contoh: "ALIM — https://alim.sch.id"
    */
    'brand_footer' => env('WA_BRAND_FOOTER', ''),

    /*
    | Toggle jenis notifikasi (dapat ditimpa settings `wa_event_{key}`).
    */
    'events' => [
        'permit_picked_up' => env('WA_EVENT_PICKED_UP', true),
        'permit_returned' => env('WA_EVENT_RETURNED', true),
        'student_sick' => env('WA_EVENT_SICK', true),
        'permit_approved' => true,
        'permit_rejected' => true,
        'violation' => true,
        'overdue' => true,
        'rapor' => true,
        'holiday' => true,
        'achievement' => true,
        'announcement' => true,
        'visit' => true,
        'reminder' => true,
        'emergency' => true,
    ],

    /*
    | Template pesan (dapat ditimpa settings `wa_template_{key}`).
    | Placeholder: {santri} {waktu} {penjemput} {tujuan} {estimasi} {catatan}
    |              {catatan_sumber} {mapel} {rombel} {judul} {tanggal} {jenis}
    |              {berangkat} {kasus} {prestasi} {pesan} {pengunjung} {jadwal}
    */
    'templates' => [
        'picked_up' => "Assalamu'alaikum Bpk/Ibu wali dari {santri},\n\nSantri *{santri}* telah *DIJEMPUT* keluar asrama.\n• Waktu: {waktu}\n• Penjemput: {penjemput}\n• Tujuan: {tujuan}\n• Estimasi kembali: {estimasi}\n\nMohon pastikan santri kembali sesuai jadwal. Terima kasih.",

        'returned' => "Assalamu'alaikum Bpk/Ibu wali dari {santri},\n\nAlhamdulillah, santri *{santri}* telah *KEMBALI KE ASRAMA*.\n• Waktu kembali: {waktu}\n\nTerima kasih atas perhatiannya.",

        'sick' => "Assalamu'alaikum Bpk/Ibu wali dari {santri},\n\nSantri *{santri}* saat ini *SAKIT* dan sedang ditangani di {catatan_sumber}.\n{catatan}\nMohon doa dan perhatiannya. Kami akan menginformasikan kembali perkembangannya.",

        'rapor' => "Assalamu'alaikum Bpk/Ibu wali,\n\n*Pemberitahuan Rapor* — nilai final untuk *{mapel}* kelas *{rombel}* ({judul}) telah dikunci dan rapor siap ditinjau.\n• Waktu: {waktu}\n\nSilakan menghubungi wali kelas bila ada pertanyaan. Terima kasih.",

        'holiday' => "Assalamu'alaikum Bpk/Ibu wali,\n\n*Pemberitahuan Libur/Jadwal*:\n• {judul}\n• Tanggal: {tanggal}\n• Jenis: {catatan_sumber}\n\nMohon diperhatikan jadwalnya. Terima kasih.",

        'permit_approved' => "Assalamu'alaikum Bpk/Ibu wali dari {santri},\n\nIzin *{jenis}* untuk santri *{santri}* telah *DISETUJUI*.\n• Berangkat: {berangkat}\n• Estimasi kembali: {estimasi}\n• Penjemput: {penjemput}\n\nMohon pastikan kembali sesuai jadwal. Terima kasih.",

        'permit_rejected' => "Assalamu'alaikum Bpk/Ibu wali dari {santri},\n\nMohon maaf, pengajuan izin *{jenis}* untuk santri *{santri}* *BELUM DAPAT DISETUJUI*.\n• Catatan: {catatan}\n\nSilakan menghubungi pengasuh/wali kelas bila perlu. Terima kasih.",

        'permit_reminder' => "Assalamu'alaikum Bpk/Ibu wali dari {santri},\n\nPengingat: izin untuk santri *{santri}* dijadwalkan *kembali ke asrama* pada *{estimasi}*. Mohon pastikan kepulangan tepat waktu. Terima kasih.",

        'violation' => "Assalamu'alaikum Bpk/Ibu wali dari {santri},\n\nKami menginformasikan adanya *pelanggaran* yang tercatat untuk santri *{santri}*:\n• {kasus}\n• Tanggal: {tanggal}\n{catatan}\nMohon bimbingan dan perhatiannya. Terima kasih.",

        'overdue' => "Assalamu'alaikum Bpk/Ibu wali dari {santri},\n\nKami informasikan santri *{santri}* *belum kembali ke asrama* melewati batas waktu izin.\n• Estimasi kembali: {estimasi}\n\nMohon konfirmasi kepada pengasuh. Terima kasih.",

        'achievement' => "Assalamu'alaikum Bpk/Ibu wali dari {santri},\n\nSelamat! Santri *{santri}* meraih *{prestasi}*.\n• Catatan: {catatan}\n\nSemoga menjadi motivasi. Terima kasih.",

        'announcement' => "Assalamu'alaikum Bpk/Ibu wali dari {santri},\n\n*{judul}*\n{pesan}\n\nTerima kasih.",

        'visit' => "Assalamu'alaikum Bpk/Ibu wali dari {santri},\n\nKunjungan untuk santri *{santri}* telah *DISETUJUI*.\n• Pengunjung: {pengunjung}\n• Jadwal: {jadwal}\n\nTerima kasih.",

        'visit_rejected' => "Assalamu'alaikum Bpk/Ibu wali dari {santri},\n\nMohon maaf, pengajuan kunjungan untuk santri *{santri}* *BELUM DAPAT DISETUJUI*.\n• Pengunjung: {pengunjung}\n• Jadwal: {jadwal}\n• Catatan: {catatan}\n\nSilakan menghubungi pengasuh bila perlu. Terima kasih.",
    ],
];
