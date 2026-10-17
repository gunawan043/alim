<?php

/**
 * LJK Smart Scanner — konfigurasi mesin OMR deterministik.
 *
 * Semua ambang batas dapat dikalibrasi ulang dengan dataset berlabel:
 *   php artisan ljk:calibrate --dataset=storage/app/ljk/datasets/demo
 *
 * Nilai default di bawah ini adalah hasil kalibrasi awal pada dataset
 * sintetis (lihat docs/features/LJK-SMART-SCANNER.md).
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Versi mesin
    |--------------------------------------------------------------------------
    | Disimpan pada setiap scan (ljk_scans.engine_version) agar hasil lama
    | tetap dapat diaudit ketika algoritma berkembang.
    */
    'engine_version' => 'omr-v1.0.0',

    /*
    |--------------------------------------------------------------------------
    | Resolusi kanonik
    |--------------------------------------------------------------------------
    | Geometri template disimpan dalam milimeter. Saat memproses gambar,
    | semua koordinat dipetakan ke ruang kanonik pada DPI berikut.
    */
    'canonical_dpi' => 200,
    'process' => [
        // Sisi terpanjang gambar kerja (piksel). Foto HP 12MP diturunkan
        // sebelum analisis agar waktu & memori proses wajar (±70 MB).
        // 1400 px ≈ 145 DPI pada A4 — cukup untuk kotak 6,2 mm.
        'max_edge_px' => 1400,
        // Batas ukuran unggahan (KB).
        'max_upload_kb' => 12288,
        // Mime yang diterima.
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        // Waktu proses maksimum sebelum dianggap gagal (ms) — untuk log.
        'budget_ms' => 20000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Gerbang kualitas gambar (quality gate)
    |--------------------------------------------------------------------------
    | Gambar yang tidak lolos tidak diproses lebih lanjut dan dikembalikan
    | sebagai `tidak_dapat_diproses` dengan alasan yang jelas.
    */
    'quality' => [
        // Varians Laplacian minimum (0-255 gray). Di bawah ini = buram.
        'min_sharpness' => 55.0,
        // Rerata kecerahan yang diterima.
        'min_brightness' => 42.0,
        'max_brightness' => 245.0,
        // Simpangan baku minimum (kontras terlalu rendah = washout).
        'min_contrast' => 18.0,
        // Marker harus ditemukan semua dan membentuk segi empat yang wajar.
        'require_all_markers' => true,
        // Luas area kertas minimum terhadap bingkai gambar.
        'min_paper_area_ratio' => 0.28,
        // Kemiringan residual setelah koreksi perspektif (derajat).
        'max_residual_skew_deg' => 4.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Deteksi tanda silang (X)
    |--------------------------------------------------------------------------
    | Setiap opsi dianalisis menjadi skor bukti 0..1. Ambang di bawah
    | menentukan klasifikasi akhir per nomor soal.
    */
    'detection' => [
        // Bobot fitur pembentuk skor X (total 1.0).
        'weights' => [
            'diagonal_pair' => 0.42,   // kedua diagonal hadir
            'corner_coverage' => 0.24, // tinta di keempat sudut kotak
            'center_crossing' => 0.16, // tinta di pusat (titik temu X)
            'coverage_area' => 0.10,   // sebaran tinta dalam kotak
            'structure_bonus' => 0.08, // pola terstruktur, bukan coretan acak
        ],
        // Bandingkan pasangan diagonal; rasio di bawah ini berarti hanya
        // satu garis diagonal → bukan X (miring/centang/garis).
        'diagonal_balance_min' => 0.34,
        // Skor opsi dianggap terisi kuat.
        'option_strong' => 0.56,
        // Skor opsi dianggap sebagai indikasi lemah.
        'option_weak' => 0.33,
        // Jarak minimum top-1 vs top-2 agar tidak dianggap ganda.
        'strong_margin' => 0.12,
        // Kandidat tunggal (tanpa anomali) dengan skor >= nilai ini diterima
        // sebagai terbaca walau di bawah option_strong (X tipis/blur ringan).
        'single_candidate_min' => 0.47,
        // Selisih maksimum antar dua kandidat kuat sebelum disebut ganda.
        'double_tolerance' => 0.10,
        // Di bawah ini dianggap kotak kosong.
        'empty_max' => 0.28,
        // Kerapatan tinta minimum agar kotak dinilai "ada tulisan".
        'ink_min_ratio' => 0.015,
        // Ambang deteksi tinta lokal: gray < localMean - max(c, localMean*ratio).
        'adaptive_c' => 10.0,
        'adaptive_ratio' => 0.22,
        // Ukuran jendela adaptive threshold (fraksi sisi terpendek gambar kerja).
        'adaptive_window_ratio' => 0.035,
        // Eksklusi tepi kotak dari analisis (fraksi dari sisi).
        'border_exclusion_ratio' => 0.16,
        // Warna tinta: selisih kanal b-r minimum untuk tinta biru.
        'blue_ink_min_delta' => 14,
        // Panjang sampling diagonal (jumlah titik per diagonal).
        'diagonal_samples' => 26,
        // Lebar pita pencarian diagonal (milimeter pada ruang kanonik).
        'diagonal_band_mm' => 0.55,
    ],

    /*
    |--------------------------------------------------------------------------
    | Status hasil baca
    |--------------------------------------------------------------------------
    */
    'statuses' => [
        'terbaca' => 'Terbaca',
        'kosong' => 'Kosong',
        'ganda' => 'Ganda',
        'meragukan' => 'Meragukan',
        'tidak_dapat_diproses' => 'Tidak dapat diproses',
        'manual' => 'Perlu penilaian manual',
    ],

    /*
    |--------------------------------------------------------------------------
    | Template default
    |--------------------------------------------------------------------------
    */
    'template' => [
        'default_code' => 'LJK-A4-PILIHAN-GANDA',
        'paper' => ['size' => 'A4', 'width_mm' => 210.0, 'height_mm' => 297.0],
        'margin_mm' => ['top' => 13.0, 'right' => 11.0, 'bottom' => 12.0, 'left' => 11.0],
        'fiducial' => ['size_mm' => 6.0, 'offset_mm' => 7.0],
        'qr' => ['size_mm' => 24.0, 'offset_top_mm' => 12.0],
        'box' => ['width_mm' => 6.2, 'height_mm' => 4.8, 'gap_mm' => 1.4],
        'row_height_mm' => 8.2,
        'column_gap_mm' => 6.0,
        // Kolom yang dipakai sejak awal (mis. 2 kolom: kiri no 1-10, kanan no 11-20).
        // Kolom tambahan otomatis dipakai bila jumlah soal tidak muat.
        'preferred_columns' => 2,
        'max_columns' => 3,
        'max_options' => 5,
        // Ruang kop lembaga + judul + identitas peserta di atas area jawaban.
        'header_height_mm' => 58.0,
        'footer_margin_mm' => 14.0,
        // Tinggi gambar kop maksimum (mm) bila sekolah mengunggah kop surat.
        'max_kop_height_mm' => 20.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Penilaian & integrasi nilai resmi
    |--------------------------------------------------------------------------
    */
    'grading' => [
        // Skala nilai akhir.
        'scale' => 100.0,
        // Jenis soal yang dapat dibaca mesin OMR (kotak pilihan).
        'auto_types' => ['pg', 'mcc', 'bs'],
        // Jenis soal yang harus dinilai manual oleh guru pada LJK kertas.
        'manual_types' => ['isian', 'uraian', 'jodoh'],
        // Bobot minimum agar tidak terjadi pembagian nol.
        'min_total_weight' => 0.01,
    ],

    // Target kolom nilai resmi (admin_nilai_sumatif) per jenis ujian.
    'nilai_target' => [
        'sts' => ['mode' => 'sts_murni', 'label' => 'Nilai Murni STS'],
        'sas' => ['mode' => 'sas_murni', 'label' => 'Nilai Murni SAS'],
        'ulangan_harian' => ['mode' => 'sumatif_column', 'label' => 'Kolom Sumatif Harian'],
        'try_out' => ['mode' => 'sumatif_column', 'label' => 'Kolom Sumatif Harian'],
        'latihan' => ['mode' => 'sumatif_column', 'label' => 'Kolom Sumatif Harian'],
    ],

    /*
    |--------------------------------------------------------------------------
    | QR / identitas mesin lembar
    |--------------------------------------------------------------------------
    */
    'qr' => [
        // Payload QR ditandatangani HMAC; server menolak lembar palsu.
        'signature_bytes' => 10,
        'version' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF & font
    |--------------------------------------------------------------------------
    | Seluruh PDF LJK memakai font Cambria. Karena Cambria berlisensi, berkas
    | font dibaca dari direktori yang dipindai di bawah ini (Cambria resmi
    | atau Caladea yang metrik-kompatibel); bila tidak ada, dipakai serif
    | bawaan Dompdf (DejaVu Serif) supaya tampilan tetap konsisten.
    */
    'pdf' => [
        'fonts' => [
            'family' => 'LJK-Cambria',
            'cache' => storage_path('app/dompdf/fonts'),
            'paths' => [
                storage_path('app/fonts'),
                storage_path('fonts'),
                base_path('resources/fonts'),
                '/usr/share/fonts/truetype/crosextra',
                '/usr/share/fonts',
                '/usr/local/share/fonts',
                '/Library/Fonts',
                '/System/Library/Fonts',
            ],
            'fallback' => 'DejaVu Serif',
            // Font Arab opsional (Amiri/Scheherazade/KFGQPC/Noto Naskh).
            // Bila tidak ada, dipakai DejaVu Sans bawaan Dompdf yang sudah
            // memuat bentuk presentasi Arab (shaping dilakukan di PHP).
            'arabic_paths' => [
                storage_path('app/fonts'),
                storage_path('fonts'),
                base_path('resources/fonts'),
                '/usr/share/fonts',
                '/usr/local/share/fonts',
                '/Library/Fonts',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Lembar soal (naskah cetak untuk santri)
    |--------------------------------------------------------------------------
    */
    'soal' => [
        // Judul per jenis ujian (baris 1) — baris 2 otomatis "TAHUN AJARAN …".
        'judul' => [
            'sts' => 'SOAL ASESMEN SUMATIF TENGAH SEMESTER',
            'sas' => 'SOAL ASESMEN SUMATIF AKHIR SEMESTER',
            'ulangan_harian' => 'SOAL ULANGAN HARIAN',
            'try_out' => 'SOAL TRY OUT',
            'latihan' => 'SOAL LATIHAN',
        ],
        // Semester disisipkan pada judul STS/SAS (mis. "… TENGAH SEMESTER GANJIL").
        'semester_pada_judul' => ['sts', 'sas'],
        'penanaman_karakter' => [
            'Berdo\'alah sebelum mengerjakan soal!',
            'Bertaqwalah kepada Allah dengan berperilaku jujur dalam mengerjakan soal!',
            'Sebaik-baik pengawas adalah Allah Subhanahuwata\'ala!',
        ],
        'instruksi' => 'Pilihlah jawaban yang paling tepat dengan memberikan tanda silang (X) pada huruf A, B, C atau D pada Lembar Jawaban yang disediakan !',
        // Ditampilkan pada lembar soal berbahasa Arab (setelah instruksi Indonesia).
        'instruksi_arab' => 'اختر الجواب الأصح بوضع علامة (X) على الحرف ا، ب، ج، د في ورقة الجواب المرفقة !',

        // ── Format cetak (default setelan umum MS Word) ─────────────────
        // Font lembar soal (pt) — Cambria.
        'font_size' => 12.0,
        // Faktor kompensasi bila font Cambria/Caladea belum dipasang: font
        // pengganti (DejaVu Serif) tampil lebih besar pada pt yang sama,
        // sehingga ukuran dikalikan faktor ini agar setara 12 pt Cambria.
        'font_size_fallback_scale' => 0.90,
        // Jarak antar baris "single" (1.0); jarak antar SOAL 1.15.
        'line_height' => 1.0,
        'question_gap' => 1.15,
        // Margin dalam inci (dikonversi otomatis ke mm): atas, bawah, kiri, kanan.
        'margin_in' => ['top' => 0.20, 'bottom' => 0.10, 'left' => 0.59, 'right' => 0.63],
        // Kertas: default A4; otomatis naik ke F4 (folio) bila menghemat halaman.
        'paper' => [
            'default' => 'a4',
            'fallback' => 'f4',
            'auto' => true,
            'sizes' => [
                'a4' => ['width_mm' => 210.0, 'height_mm' => 297.0, 'label' => 'A4'],
                'f4' => ['width_mm' => 215.0, 'height_mm' => 330.0, 'label' => 'F4 (Folio)'],
            ],
        ],
        // Huruf pilihan ganda untuk soal berbahasa Arab (bukan A/B/C/D/E).
        'huruf_arab' => ['ا', 'ب', 'ج', 'د', 'هـ'],
        // Identitas: jumlah baris maksimum pada tabel kop (2 pasang per baris).
        'identitas_baris_maks' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Penyimpanan
    |--------------------------------------------------------------------------
    */
    'storage' => [
        // Disk privat untuk gambar scan (tidak dapat diakses publik).
        'scan_disk' => 'local',
        'scan_folder' => 'ljk/scans',
    ],
];
