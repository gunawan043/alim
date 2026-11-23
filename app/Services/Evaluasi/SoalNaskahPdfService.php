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

        $first = $questions->first();
        $academicYear = $first?->bankSoal?->academicYear
            ?? AcademicYear::query()->where('is_active', true)->first();

        $judulLines = $this->judulLines($judul, $academicYear);

        $paperKey = match (strtoupper((string) ($settings['paper'] ?? 'A4'))) {
            'F4', 'FOLIO' => 'f4',
            'LETTER' => 'letter',
            default => 'a4',
        };

        $paper = $sheet->paper($paperKey);

        $identityRows = [
            [
                ['Mata Pelajaran', (string) ($first?->bankSoal?->subject?->name ?? '-')],
                ['Kelas', $kelasLabel ?: (string) ($first?->bankSoal?->gradeLevel?->name ?? '-')],
            ],
            [
                ['Guru Pengampu', (string) $user->name],
                ['Nama Peserta Didik', ''],
            ],
        ];

        return [
            'judul' => $judulLines,
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
            'questionGap' => 1.15,
            'mataPelajaran' => (string) ($first?->bankSoal?->subject?->name ?? '-'),
            'kelas' => $kelasLabel ?: (string) ($first?->bankSoal?->gradeLevel?->name ?? '-'),
            'guruPengampu' => (string) $user->name,
            'namaPeserta' => '',
            'karakter' => (array) config('ljk.soal.penanaman_karakter', []),
            'instruksi' => (string) config('ljk.soal.instruksi', ''),
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
     * @return array{0: string, 1: string}
     */
    private function judulLines(?string $judul, ?AcademicYear $academicYear): array
    {
        $line1 = trim((string) $judul);

        if ($line1 === '') {
            $line1 = 'NASKAH SOAL';
        }

        $tahun = trim((string) ($academicYear?->name ?? ''));

        return [$line1, $tahun !== '' ? 'TAHUN AJARAN '.$tahun : ''];
    }
}
