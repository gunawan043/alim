<?php

declare(strict_types=1);

namespace App\Services\Evaluasi;

use App\Models\Soal;
use Illuminate\Http\Request;

/**
 * Pengaturan cetak naskah soal per guru.
 *
 * Disimpan di session agar tidak perlu tabel baru dan tetap melekat pada guru
 * yang sedang login. Setiap cetak berikutnya memakai pengaturan terakhir.
 *
 * Kelompok pengaturan:
 *  - halaman   : kertas, orientasi, margin
 *  - judul     : isi judul, tahun ajaran, font, rata
 *  - identitas : tabel keterangan (label, jumlah baris, garis, padding)
 *  - karakter  : penanaman karakter (judul, butir, font)
 *  - instruksi : keterangan/instruksi pengerjaan soal
 *  - soal      : font, spasi, jarak, kolom opsi
 */
class PrintSettingsService
{
    /**
     * Versi skema pengaturan. Naikkan angka ini bila nilai bawaan sistem
     * berubah, agar pengaturan lama di session tidak menimpa default baru.
     */
    private const VERSION = 'v2';

    private const SESSION_KEY = 'soal_print_settings_'.self::VERSION;

    public const PAPERS = [
        'A4' => 'A4 (210 × 297 mm)',
        'F4' => 'F4 / Folio (215 × 330 mm)',
        'Letter' => 'Letter (216 × 279 mm)',
    ];

    public const FONTS = [
        'times' => 'Times New Roman',
        'arial' => 'Arial',
        'cambria' => 'Cambria',
    ];

    /**
     * Nilai bawaan untuk guru yang belum pernah mengatur.
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            // Halaman
            'paper' => 'A4',
            'orientation' => 'portrait',
            'margin_top' => 5,
            'margin_right' => 20,
            'margin_bottom' => 10,
            'margin_left' => 25,

            // Judul & tahun ajaran
            'judul' => '',
            'judul_size' => 12,
            'judul_bold' => true,
            'judul_line_height' => 1.2,
            'judul_align' => 'center',
            'show_tahun_ajaran' => true,
            'tahun_ajaran' => '',
            'tahun_size' => 12,
            'tahun_bold' => true,
            'tahun_line_height' => 1.2,

            // Tabel keterangan (identitas)
            'show_identitas' => true,
            'identitas_size' => 11,
            'identitas_rows' => 2,
            'identitas_border' => true,
            'identitas_padding' => 1.2,
            'identitas_label_mapel' => 'Mata Pelajaran',
            'identitas_label_kelas' => 'Kelas',
            'identitas_label_guru' => 'Guru Pengampu',
            'identitas_label_peserta' => 'Nama Peserta Didik',
            'identitas_isi_mapel' => '',
            'identitas_isi_kelas' => '',
            'identitas_isi_guru' => '',

            // Penanaman karakter
            'show_karakter' => true,
            'karakter_title' => 'PENANAMAN KARAKTER :',
            'karakter_items' => '',
            'karakter_size' => 11,
            'karakter_bold' => false,
            'karakter_line_height' => 1.0,
            'karakter_style' => 'numbered',

            // Keterangan / instruksi soal
            'show_instruksi' => true,
            'instruksi' => '',
            'instruksi_size' => 11,
            'instruksi_bold' => false,
            'instruksi_italic' => false,
            'instruksi_spacing' => 1.0,

            // Soal & opsi
            'font' => 'cambria',
            'font_size' => 11,
            'line_height' => 1.0,
            'question_gap' => 1.25,
            // Batas atas jumlah kolom opsi. 0 = otomatis (cari yang paling
            // padat). 2/3/4 memaksa susunan tidak lebih dari angka tsb.
            'option_columns' => 0,
            'option_indent' => 2,
            'start_number' => 1,

            // Kunci
            'show_key' => false,

            // Kop: otomatis tampil, lebar penuh s.d. margin, tanpa garis.
            // Gambar kop sekolah biasanya SUDAH memuat garis sendiri.
            'show_kop' => true,
            'kop_width_pct' => 100,
            'kop_rule' => false,
        ];
    }

    /** @return array<string, mixed> */
    public function get(): array
    {
        $stored = session(self::SESSION_KEY, []);

        return array_merge($this->defaults(), is_array($stored) ? $stored : []);
    }

    /**
     * Ambil pengaturan dari request bila dikirim, lalu simpan sebagai default.
     *
     * @return array<string, mixed>
     */
    public function resolve(Request $request): array
    {
        $settings = $this->preview($request);

        session([self::SESSION_KEY => $settings]);

        return $settings;
    }

    /**
     * Ambil pengaturan tanpa menyimpannya (untuk pratinjau).
     *
     * @return array<string, mixed>
     */
    public function preview(Request $request): array
    {
        if (! $request->hasAny(array_keys($this->defaults()))) {
            return $this->get();
        }

        return $this->sanitize(array_merge($this->get(), $this->fromRequest($request)));
    }

    /** @return array<string, mixed> */
    private function fromRequest(Request $request): array
    {
        $values = [];

        foreach (array_keys($this->defaults()) as $field) {
            if (! $request->exists($field)) {
                continue;
            }

            $values[$field] = $request->input($field);
        }

        // Checkbox tidak dikirim saat tidak dicentang.
        foreach ($this->booleanFields() as $checkbox) {
            $values[$checkbox] = $request->boolean($checkbox);
        }

        return $values;
    }

    /** @return array<int, string> */
    private function booleanFields(): array
    {
        return [
            'judul_bold', 'show_tahun_ajaran', 'tahun_bold',
            'show_identitas', 'identitas_border',
            'show_karakter', 'karakter_bold',
            'show_instruksi', 'instruksi_bold', 'instruksi_italic',
            'show_key', 'show_kop', 'kop_rule',
        ];
    }

    /**
     * Batasi nilai ke rentang wajar agar naskah tidak rusak.
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function sanitize(array $settings): array
    {
        // Halaman
        $settings['paper'] = array_key_exists((string) $settings['paper'], self::PAPERS) ? $settings['paper'] : 'A4';
        $settings['orientation'] = $settings['orientation'] === 'landscape' ? 'landscape' : 'portrait';

        foreach (['margin_top', 'margin_right', 'margin_bottom', 'margin_left'] as $side) {
            $settings[$side] = $this->number($settings[$side] ?? null, 5, 50, 20);
        }

        // Judul
        $settings['judul'] = mb_substr(trim((string) $settings['judul']), 0, 150);
        $settings['judul_size'] = (int) $this->number($settings['judul_size'] ?? null, 8, 24, 12);
        $settings['judul_bold'] = (bool) $settings['judul_bold'];
        $settings['judul_line_height'] = round($this->number($settings['judul_line_height'] ?? null, 1.0, 2.5, 1.2), 2);
        $settings['judul_align'] = in_array($settings['judul_align'] ?? 'center', ['center', 'left', 'right'], true)
            ? $settings['judul_align']
            : 'center';

        // Tahun ajaran
        $settings['show_tahun_ajaran'] = (bool) $settings['show_tahun_ajaran'];
        $settings['tahun_ajaran'] = mb_substr(trim((string) $settings['tahun_ajaran']), 0, 60);
        $settings['tahun_size'] = (int) $this->number($settings['tahun_size'] ?? null, 8, 24, 12);
        $settings['tahun_bold'] = (bool) $settings['tahun_bold'];
        $settings['tahun_line_height'] = round($this->number($settings['tahun_line_height'] ?? null, 1.0, 2.5, 1.2), 2);

        // Tabel keterangan
        $settings['show_identitas'] = (bool) $settings['show_identitas'];
        $settings['identitas_size'] = (int) $this->number($settings['identitas_size'] ?? null, 8, 16, 11);
        $settings['identitas_rows'] = (int) $this->number($settings['identitas_rows'] ?? null, 1, 3, 2);
        $settings['identitas_border'] = (bool) $settings['identitas_border'];
        $settings['identitas_padding'] = round($this->number($settings['identitas_padding'] ?? null, 0, 4, 1.2), 2);

        foreach (['mapel', 'kelas', 'guru', 'peserta'] as $field) {
            $key = 'identitas_label_'.$field;
            $settings[$key] = mb_substr(trim((string) $settings[$key]), 0, 40);
        }

        foreach (['mapel', 'kelas', 'guru'] as $field) {
            $key = 'identitas_isi_'.$field;
            $settings[$key] = mb_substr(trim((string) $settings[$key]), 0, 80);
        }

        // Penanaman karakter
        $settings['show_karakter'] = (bool) $settings['show_karakter'];
        $settings['karakter_title'] = mb_substr(trim((string) $settings['karakter_title']), 0, 80);
        $settings['karakter_items'] = mb_substr((string) $settings['karakter_items'], 0, 2000);
        $settings['karakter_size'] = (int) $this->number($settings['karakter_size'] ?? null, 8, 16, 11);
        $settings['karakter_bold'] = (bool) $settings['karakter_bold'];
        $settings['karakter_line_height'] = round($this->number($settings['karakter_line_height'] ?? null, 1.0, 2.5, 1.0), 2);
        $settings['karakter_style'] = in_array($settings['karakter_style'] ?? 'numbered', ['numbered', 'bullet', 'dash'], true)
            ? $settings['karakter_style']
            : 'numbered';

        // Instruksi
        $settings['show_instruksi'] = (bool) $settings['show_instruksi'];
        $settings['instruksi'] = mb_substr((string) $settings['instruksi'], 0, 2000);
        $settings['instruksi_size'] = (int) $this->number($settings['instruksi_size'] ?? null, 8, 16, 11);
        $settings['instruksi_bold'] = (bool) $settings['instruksi_bold'];
        $settings['instruksi_italic'] = (bool) $settings['instruksi_italic'];
        $settings['instruksi_spacing'] = round($this->number($settings['instruksi_spacing'] ?? null, 1.0, 2.5, 1.0), 2);

        // Soal & opsi
        $settings['font'] = array_key_exists((string) $settings['font'], self::FONTS) ? $settings['font'] : 'times';
        $settings['font_size'] = (int) $this->number($settings['font_size'] ?? null, 8, 16, 11);
        $settings['line_height'] = round($this->number($settings['line_height'] ?? null, 1.0, 2.5, 1.2), 2);
        $settings['question_gap'] = round($this->number($settings['question_gap'] ?? null, 0.5, 4, 1.5), 2);
        $settings['option_columns'] = in_array((int) $settings['option_columns'], [0, 2, 3, 4], true)
            ? (int) $settings['option_columns']
            : 0;
        $settings['option_indent'] = (int) $this->number($settings['option_indent'] ?? null, 0, 15, 2);
        $settings['start_number'] = (int) $this->number($settings['start_number'] ?? null, 1, 999, 1);

        // Kunci
        $settings['show_key'] = (bool) $settings['show_key'];

        // Kop
        $settings['show_kop'] = (bool) $settings['show_kop'];
        $settings['kop_width_pct'] = (int) $this->number($settings['kop_width_pct'] ?? null, 30, 100, 100);
        $settings['kop_rule'] = (bool) $settings['kop_rule'];

        return $settings;
    }

    /**
     * Butir penanaman karakter: satu butir per baris.
     *
     * @param  array<string, mixed>  $settings
     * @return array<int, string>
     */
    public function karakterItems(array $settings): array
    {
        $raw = trim((string) ($settings['karakter_items'] ?? ''));

        if ($raw !== '') {
            return collect(preg_split('/\R/', $raw))
                ->map(fn ($line) => trim((string) $line))
                ->filter()
                ->values()
                ->all();
        }

        return (array) config('ljk.soal.penanaman_karakter', []);
    }

    /**
     * Instruksi: pakai isian guru, atau bawaan konfigurasi.
     *
     * @param  array<string, mixed>  $settings
     */
    public function instruksi(array $settings): string
    {
        $raw = trim((string) ($settings['instruksi'] ?? ''));

        return $raw !== '' ? $raw : (string) config('ljk.soal.instruksi', '');
    }

    /**
     * Soal STS/SAS wajib tervalidasi sebelum dicetak sebagai naskah formal;
     * soal harian milik guru tidak memerlukan validasi.
     *
     * @param  iterable<Soal>  $questions
     * @return array<int, string> potongan teks soal yang memblokir
     */
    public function blockedQuestions(iterable $questions): array
    {
        $blocked = [];

        foreach ($questions as $soal) {
            if ($soal->requiresValidation() && ! $soal->isValidated()) {
                $blocked[] = mb_substr(strip_tags((string) $soal->pertanyaan), 0, 60);
            }
        }

        return $blocked;
    }

    private function number(mixed $value, float $min, float $max, float $default): float
    {
        if (! is_numeric($value)) {
            return $default;
        }

        return round(max($min, min($max, (float) $value)), 2);
    }
}
