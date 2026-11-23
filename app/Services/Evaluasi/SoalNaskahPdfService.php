<?php

declare(strict_types=1);

namespace App\Services\Evaluasi;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Soal;
use App\Models\User;
use App\Services\Ljk\LjkArabicText;
use App\Services\Ljk\LjkPdfFonts;
use App\Services\Ljk\LjkSchoolLetterhead;
use App\Services\Ljk\LjkSoalSheet;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Collection;

/**
 * Naskah soal dari daftar Soal langsung — tanpa paket soal.
 *
 * Memakai format yang SAMA dengan naskah STS/SAS (`ljk.soal-pdf`) agar guru
 * mencetak soal harian dengan kop, tabel identitas, penanaman karakter, dan
 * opsi sejajar. Sebelumnya format rapi hanya dapat dicapai lewat PaketSoal.
 */
class SoalNaskahPdfService
{
    /**
     * @param  Collection<int, Soal>  $questions
     */
    public function html(
        Collection $questions,
        School $school,
        User $user,
        array $settings,
        ?string $judul = null,
        ?string $kelasLabel = null
    ): string {
        return view('ljk.soal-pdf', $this->data($questions, $school, $user, $settings, $judul, $kelasLabel))->render();
    }

    /**
     * @param  Collection<int, Soal>  $questions
     */
    public function pdf(
        Collection $questions,
        School $school,
        User $user,
        array $settings,
        ?string $judul = null,
        ?string $kelasLabel = null
    ): string {
        $data = $this->data($questions, $school, $user, $settings, $judul, $kelasLabel);

        // Konfigurasi PDF disamakan dengan naskah LJK/paket agar font dan
        // ukuran kertas identik dengan naskah STS/SAS.
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        LjkPdfFonts::configure($options);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->html($questions, $school, $user, $settings, $judul, $kelasLabel));
        $paper = $data['paper'];
        $dompdf->setPaper([0, 0, $paper['width_mm'] * 2.834645669, $paper['height_mm'] * 2.834645669]);
        $dompdf->render();

        return (string) $dompdf->output();
    }

    /**
     * @param  Collection<int, Soal>  $questions
     * @return array<string, mixed>
     */
    public function data(
        Collection $questions,
        School $school,
        User $user,
        array $settings,
        ?string $judul = null,
        ?string $kelasLabel = null
    ): array {
        $sheet = app(LjkSoalSheet::class);
        $startNumber = max(1, (int) ($settings['start_number'] ?? 1));

        $soal = $questions->values()->map(function (Soal $item, int $index) use ($startNumber) {
            $opsi = $item->options
                ->sortBy('urutan')
                ->values()
                ->map(fn ($option) => [
                    'label' => (string) $option->label,
                    'teks' => (string) $option->teks_opsi,
                    'display_label' => (string) $option->label,
                ])
                ->all();

            $wacana = $item->wacana;

            return [
                'nomor' => $index + $startNumber,
                'pertanyaan' => (string) $item->pertanyaan,
                'opsi' => $opsi,
                'bahasa' => (string) ($item->bahasa ?? 'latin'),
                'isArabic' => false,
                'rtl' => false,
                'wacana' => $wacana ? [
                    'id' => $wacana->id,
                    'judul' => $wacana->judul,
                    'teks' => (string) $wacana->teks,
                    'sumber' => $wacana->sumber,
                ] : null,
                'gambar_path' => $item->gambar_path,
                'gambar_keterangan' => $item->gambar_keterangan,
            ];
        })->all();

        $blocks = $sheet->blocks($soal);

        foreach ($blocks as &$block) {
            if ($block['type'] === 'soal') {
                $block['nomor_tampil'] = (string) $block['nomor'].'.';
            }
        }
        unset($block);

        $maxOptions = 2;
        foreach ($soal as $butir) {
            $maxOptions = max($maxOptions, count($butir['opsi']));
        }

        // ── Tata letak pilihan jawaban ────────────────────────────────────
        // Lebar area opsi = lebar kertas - margin kiri/kanan - kolom nomor
        // soal (4,5%) agar pilihan A..D sejajar dengan teks soal.
        $paperKeyForWidth = match (strtoupper((string) ($settings['paper'] ?? 'A4'))) {
            'F4', 'FOLIO' => 'f4',
            'LETTER' => 'letter',
            default => 'a4',
        };
        $paperForWidth = $sheet->paper($paperKeyForWidth);
        $contentMm = ($paperForWidth['width_mm']
            - (float) ($settings['margin_left'] ?? 25)
            - (float) ($settings['margin_right'] ?? 20)) * 0.955;

        $layout = new SoalOpsiLayout;
        // 0 = otomatis: jumlah kolom = jumlah opsi (A–D → 4, A–E → 5).
        // Selain 0, nilai itu menjadi batas atas jumlah kolom.
        $maxColumns = (int) ($settings['option_columns'] ?? 0);
        $maxColumns = $maxColumns > 0 ? min(SoalOpsiLayout::MAX_COLUMNS, $maxColumns) : 0;

        foreach ($blocks as &$block) {
            if ($block['type'] !== 'soal' || empty($block['opsi'])) {
                continue;
            }

            $block['opsiLayout'] = $layout->layout(
                $block['opsi'],
                $contentMm,
                (float) ($settings['font_size'] ?? 11),
                $maxColumns
            );
        }
        unset($block);

        $first = $questions->first();
        $academicYear = $first?->bankSoal?->academicYear
            ?? AcademicYear::query()->where('is_active', true)->first();

        $print = app(PrintSettingsService::class);

        $judulLines = $this->judulLines($judul, $academicYear, $settings);

        $paperKey = match (strtoupper((string) ($settings['paper'] ?? 'A4'))) {
            'F4', 'FOLIO' => 'f4',
            'LETTER' => 'letter',
            default => 'a4',
        };

        $paper = $sheet->paper($paperKey);

        // Orientasi: tukar lebar/tinggi kertas.
        if (($settings['orientation'] ?? 'portrait') === 'landscape') {
            $paper['width_mm'] = $paper['height_mm'];
            $paper['height_mm'] = $sheet->paper($paperKey)['width_mm'];
        }

        $mapel = (string) ($first?->bankSoal?->subject?->name ?? '-');
        $kelas = $kelasLabel ?: (string) ($first?->bankSoal?->gradeLevel?->name ?? '-');

        $identityRows = $this->identityRows($settings, [
            'mapel' => $mapel,
            'kelas' => $kelas,
            'guru' => (string) $user->name,
        ]);

        $karakter = ! ($settings['show_karakter'] ?? true) ? [] : $print->karakterItems($settings);

        return [
            'judul' => $judulLines,
            'identitas' => [
                'show' => (bool) ($settings['show_identitas'] ?? true),
                'border' => (bool) ($settings['identitas_border'] ?? true),
                'padding' => (float) ($settings['identitas_padding'] ?? 1.2),
                'size' => (int) ($settings['identitas_size'] ?? 11),
                'labelPeserta' => (string) ($settings['identitas_label_peserta'] ?? 'Nama Peserta Didik'),
            ],
            'judulStyle' => $this->judulStyle($settings),
            'tahunStyle' => $this->tahunStyle($settings),
            'karakterStyle' => [
                'title' => (string) ($settings['karakter_title'] ?? 'PENANAMAN KARAKTER :'),
                'size' => (int) ($settings['karakter_size'] ?? 11),
                'bold' => (bool) ($settings['karakter_bold'] ?? false),
                'lineHeight' => (float) ($settings['karakter_line_height'] ?? 1.0),
                'marker' => (string) ($settings['karakter_style'] ?? 'numbered'),
            ],
            'instruksiStyle' => [
                'show' => (bool) ($settings['show_instruksi'] ?? true),
                'size' => (int) ($settings['instruksi_size'] ?? 11),
                'bold' => (bool) ($settings['instruksi_bold'] ?? false),
                'italic' => (bool) ($settings['instruksi_italic'] ?? false),
                'spacing' => (float) ($settings['instruksi_spacing'] ?? 1.0),
            ],
            'showKop' => (bool) ($settings['show_kop'] ?? true),
            'kopWidthPct' => (float) ($settings['kop_width_pct'] ?? 100),
            'kopRule' => (bool) ($settings['kop_rule'] ?? false),
            // Kop naskah harian dibiarkan selebar area margin; batas tinggi
            // dilonggarkan agar gambar kop lebar (rasio kecil) tidak menyusut.
            'kopMaxHeightMm' => 40.0,
            'showKey' => (bool) ($settings['show_key'] ?? false),
            'optionIndent' => (int) ($settings['option_indent'] ?? 2),
            'identityRows' => $identityRows,
            'letterhead' => app(LjkSchoolLetterhead::class)->data($school),
            'paper' => $paper,
            'marginMm' => [
                'top' => (float) ($settings['margin_top'] ?? 20),
                'right' => (float) ($settings['margin_right'] ?? 20),
                'bottom' => (float) ($settings['margin_bottom'] ?? 20),
                'left' => (float) ($settings['margin_left'] ?? 25),
            ],
            'lineHeight' => (float) ($settings['line_height'] ?? 1.0),
            'questionGap' => (float) ($settings['question_gap'] ?? 1.5),
            'mataPelajaran' => $mapel,
            'kelas' => $kelas,
            'guruPengampu' => (string) $user->name,
            'namaPeserta' => '',
            'karakter' => $karakter,
            'instruksi' => ($settings['show_instruksi'] ?? true) ? $print->instruksi($settings) : '',
            'instruksiArab' => (string) config('ljk.soal.instruksi_arab', ''),
            'soal' => $soal,
            'blocks' => $blocks,
            'examBahasa' => 'latin',
            'arabAngka' => false,
            'maxOptions' => min(5, $maxOptions),
            'fontStack' => LjkPdfFonts::stack(),
            'arabicStack' => LjkPdfFonts::arabicStack(),
            'arabicFaceCss' => LjkPdfFonts::arabicFaceCss(),
            'hasArabic' => collect($soal)->contains(fn ($q) => LjkArabicText::hasArabic($q['pertanyaan'])
                || collect($q['opsi'])->contains(fn ($o) => LjkArabicText::hasArabic($o['teks']))),
            'fontSize' => round((float) ($settings['font_size'] ?? 12) * LjkPdfFonts::sizeScale(), 2),
            'fontSizeBase' => (float) ($settings['font_size'] ?? 12),
            'fontSizeScaled' => ! LjkPdfFonts::hasCambria(),
            'faceCss' => LjkPdfFonts::faceCss(),
            'canonical' => true,
        ];
    }

    /**
     * Baris judul: [judul naskah, baris tahun ajaran].
     *
     * @param  array<string, mixed>  $settings
     * @return array{0: string, 1: string}
     */
    private function judulLines(?string $judul, ?AcademicYear $academicYear, array $settings): array
    {
        $line1 = trim((string) $judul);

        if ($line1 === '') {
            $line1 = trim((string) ($settings['judul'] ?? ''));
        }

        if ($line1 === '') {
            $line1 = 'NASKAH SOAL';
        }

        if (! ($settings['show_tahun_ajaran'] ?? true)) {
            return [$line1, ''];
        }

        $tahun = trim((string) ($settings['tahun_ajaran'] ?? ''));

        if ($tahun === '') {
            $tahun = trim((string) ($academicYear?->name ?? ''));
        }

        return [$line1, $tahun !== '' ? (str_starts_with(mb_strtoupper($tahun), 'TAHUN AJARAN') ? $tahun : 'TAHUN AJARAN '.$tahun) : ''];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array{size: int, bold: bool, lineHeight: float, align: string}
     */
    private function judulStyle(array $settings): array
    {
        $align = $settings['judul_align'] ?? 'center';

        return [
            'size' => (int) ($settings['judul_size'] ?? 12),
            'bold' => (bool) ($settings['judul_bold'] ?? true),
            'lineHeight' => (float) ($settings['judul_line_height'] ?? 1.2),
            'align' => in_array($align, ['center', 'left', 'right'], true) ? (string) $align : 'center',
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array{size: int, bold: bool, lineHeight: float}
     */
    private function tahunStyle(array $settings): array
    {
        return [
            'size' => (int) ($settings['tahun_size'] ?? 12),
            'bold' => (bool) ($settings['tahun_bold'] ?? true),
            'lineHeight' => (float) ($settings['tahun_line_height'] ?? 1.2),
        ];
    }

    /**
     * Tabel keterangan: label dapat diubah guru, isi boleh ditimpa manual.
     *
     * Baris 1: Mapel | Kelas. Baris 2: Guru Pengampu | Nama Peserta Didik.
     * Bila `identitas_rows` = 1 hanya baris mapel/kelas yang dicetak.
     *
     * @param  array<string, mixed>  $settings
     * @param  array{mapel: string, kelas: string, guru: string}  $values
     * @return array<int, array<int, array{0: string, 1: string}>>
     */
    private function identityRows(array $settings, array $values): array
    {
        if (! ($settings['show_identitas'] ?? true)) {
            return [];
        }

        $override = function (string $field, string $fallback) use ($settings): string {
            $value = trim((string) ($settings['identitas_isi_'.$field] ?? ''));

            return $value !== '' ? $value : $fallback;
        };

        $rows = [
            [
                [(string) ($settings['identitas_label_mapel'] ?? 'Mata Pelajaran'), $override('mapel', $values['mapel'])],
                [(string) ($settings['identitas_label_kelas'] ?? 'Kelas'), $override('kelas', $values['kelas'])],
            ],
        ];

        if ((int) ($settings['identitas_rows'] ?? 2) >= 2) {
            $rows[] = [
                [(string) ($settings['identitas_label_guru'] ?? 'Guru Pengampu'), $override('guru', $values['guru'])],
                [(string) ($settings['identitas_label_peserta'] ?? 'Nama Peserta Didik'), ''],
            ];
        }

        return $rows;
    }
}
