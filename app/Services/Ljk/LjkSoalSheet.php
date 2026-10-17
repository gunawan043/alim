<?php

declare(strict_types=1);

namespace App\Services\Ljk;

use App\Models\LjkExam;
use App\Models\LjkParticipant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Lembar soal (naskah cetak untuk santri).
 *
 * Format kop mengikuti naskah madrasah:
 *   SOAL ASESMEN SUMATIF TENGAH SEMESTER GANJIL
 *   TAHUN AJARAN 2026/2027
 *   Mata Pelajaran / Hari-Tanggal / Kelas / Waktu / Guru Pengampu / Nama Peserta Didik
 *   PENANAMAN KARAKTER (3 butir)
 *   A. Pilihlah jawaban …
 *
 * Catatan: lembar ini TIDAK memuat bobot/poin, jenis soal, TP, kunci, maupun
 * pembahasan — hanya nomor, pertanyaan, dan pilihan jawaban. Opsi jawaban
 * mengalir mendatar; hanya turun ke baris berikutnya bila tidak muat.
 */
class LjkSoalSheet
{
    /**
     * @return array<string, mixed>
     */
    public function data(LjkExam $exam, ?string $paperKey = null): array
    {
        $exam->loadMissing(['subject', 'studyGroup.gradeLevel', 'adminBook.teacher', 'academicYear', 'paketSoal']);

        $examBahasa = $this->examBahasa($exam);
        $arabAngka = $examBahasa === 'arab' && (string) config('ljk.soal.angka_arab', 'auto') === 'auto';

        $soal = $this->questions($exam, $examBahasa);
        $blocks = $this->blocks($soal);

        // Nomor tampil: ١، ٢، ٣ untuk naskah Arab; 1, 2, 3 untuk lainnya.
        foreach ($blocks as &$block) {
            if ($block['type'] === 'soal') {
                $block['nomor_tampil'] = $this->formatNumber((int) $block['nomor'], $arabAngka);
            } elseif ($block['dari'] !== null) {
                $block['dari_tampil'] = $this->formatNumber((int) $block['dari'], $arabAngka, false);
                $block['sampai_tampil'] = $this->formatNumber((int) $block['sampai'], $arabAngka, false);
            }
        }
        unset($block);

        $maxOptions = 2;
        foreach ($soal as $butir) {
            $maxOptions = max($maxOptions, count($butir['opsi']));
        }

        $paper = $this->paper($paperKey);
        $marginMm = $this->marginMm();
        $letterhead = app(LjkSchoolLetterhead::class)->data($exam->school);

        // Identitas dalam bentuk tabel: 2 pasang label/nilai per baris, maks 3 baris.
        $identityRows = $this->identityRows($exam);

        return [
            'judul' => $this->judul($exam),
            'identityRows' => $identityRows,
            'letterhead' => $letterhead,
            'paper' => $paper,
            'marginMm' => $marginMm,
            'lineHeight' => (float) config('ljk.soal.line_height', 1.0),
            'questionGap' => (float) config('ljk.soal.question_gap', 1.15),
            'mataPelajaran' => (string) ($exam->subject?->name ?? '-'),
            'hariTanggal' => $this->dayDate($exam->tanggal),
            'kelas' => $this->kelasLabel($exam),
            'waktu' => $this->waktuLabel($exam),
            'guruPengampu' => (string) ($exam->adminBook?->teacher?->name ?? '-'),
            'namaPeserta' => '',
            'karakter' => (array) config('ljk.soal.penanaman_karakter', []),
            'instruksi' => (string) config('ljk.soal.instruksi', ''),
            'instruksiArab' => (string) config('ljk.soal.instruksi_arab', ''),
            'soal' => $soal,
            'blocks' => $blocks,
            'examBahasa' => $examBahasa,
            'arabAngka' => $arabAngka,
            'maxOptions' => min(5, $maxOptions),
            'fontStack' => LjkPdfFonts::stack(),
            'arabicStack' => LjkPdfFonts::arabicStack(),
            'arabicFaceCss' => LjkPdfFonts::arabicFaceCss(),
            'hasArabic' => LjkArabicText::hasArabic($exam->judul)
                || collect($soal)->contains(fn ($q) => LjkArabicText::hasArabic($q['pertanyaan'])
                    || collect($q['opsi'])->contains(fn ($o) => LjkArabicText::hasArabic($o['teks']))
                    || LjkArabicText::hasArabic($q['wacana']['teks'] ?? null)),
            'fontSize' => round(
                (float) config('ljk.soal.font_size', 12.0) * LjkPdfFonts::sizeScale(),
                2
            ),
            'fontSizeBase' => (float) config('ljk.soal.font_size', 12.0),
            'fontSizeScaled' => ! LjkPdfFonts::hasCambria(),
            'faceCss' => LjkPdfFonts::faceCss(),
            'canonical' => app(LjkExamService::class)->usesCanonicalOrder($exam),
        ];
    }

    public function render(LjkExam $exam, ?string $paperKey = null): string
    {
        return view('ljk.soal-pdf', $this->data($exam, $paperKey))->render();
    }

    /**
     * Ukuran halaman (mm) + label untuk kunci kertas ('a4'|'f4').
     *
     * @return array{key: string, width_mm: float, height_mm: float, label: string}
     */
    public function paper(?string $key = null): array
    {
        $sizes = (array) config('ljk.soal.paper.sizes', []);
        $key = $key ?? (string) config('ljk.soal.paper.default', 'a4');

        if (! isset($sizes[$key])) {
            $key = (string) config('ljk.soal.paper.default', 'a4');
        }

        $size = $sizes[$key] ?? ['width_mm' => 210.0, 'height_mm' => 297.0, 'label' => strtoupper($key)];

        return [
            'key' => $key,
            'width_mm' => (float) $size['width_mm'],
            'height_mm' => (float) $size['height_mm'],
            'label' => (string) ($size['label'] ?? strtoupper($key)),
        ];
    }

    /**
     * Margin halaman dalam mm (dari setelan inci).
     *
     * @return array{top: float, bottom: float, left: float, right: float}
     */
    public function marginMm(): array
    {
        $inch = (array) config('ljk.soal.margin_in', ['top' => 0.2, 'bottom' => 0.1, 'left' => 0.59, 'right' => 0.63]);

        return [
            'top' => round(((float) ($inch['top'] ?? 0.2)) * 25.4, 2),
            'bottom' => round(((float) ($inch['bottom'] ?? 0.1)) * 25.4, 2),
            'left' => round(((float) ($inch['left'] ?? 0.59)) * 25.4, 2),
            'right' => round(((float) ($inch['right'] ?? 0.63)) * 25.4, 2),
        ];
    }

    /**
     * Bahasa naskah efektif: tersimpan pada ujian, atau diturunkan dari butir.
     */
    public function examBahasa(LjkExam $exam): string
    {
        $bahasa = (string) ($exam->bahasa ?? '');

        if (in_array($bahasa, ['latin', 'arab', 'gabungan'], true)) {
            return $bahasa;
        }

        if (! $exam->paketSoal) {
            return 'latin';
        }

        $modes = $exam->paketSoal->items()->with('soal')->get()
            ->map(fn ($item) => $item->soal?->bahasaEfektif() ?? 'latin');

        return $modes->every(fn ($m) => $m === 'arab')
            ? 'arab'
            : ($modes->contains('arab') ? 'gabungan' : 'latin');
    }

    /**
     * Format nomor: ١، ٢، ٣ (Arab) atau 1, 2, 3 (Latin).
     */
    public function formatNumber(int $number, bool $arab, bool $withDot = true): string
    {
        $digits = (string) $number;

        if ($arab) {
            $digits = strtr($digits, [
                '0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤',
                '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩',
            ]);
        }

        return $digits.($withDot ? '.' : '');
    }

    /**
     * Nomor soal yang memakai format Arab (untuk huruf & nomor lembar jawaban).
     *
     * @return array<int, int>
     */
    public function arabicQuestionNumbers(LjkExam $exam): array
    {
        return $this->examBahasa($exam) === 'arab'
            ? collect($this->questions($exam))->pluck('nomor')->map(fn ($n) => (int) $n)->values()->all()
            : [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     * @return array<int, int>
     */
    public function arabicNumbersFrom(array $questions): array
    {
        return collect($questions)
            ->filter(fn (array $q) => (bool) ($q['isArabic'] ?? false))
            ->pluck('nomor')
            ->map(fn ($n) => (int) $n)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, string>
     */
    public function rangesFromBlocks(array $blocks): array
    {
        $ranges = [];

        foreach ($blocks as $block) {
            if (($block['type'] ?? null) === 'wacana' && ($block['dari'] ?? null) !== null) {
                $ranges[] = $block['dari'] === $block['sampai']
                    ? (string) $block['dari']
                    : $block['dari'].'–'.$block['sampai'];
            }
        }

        return $ranges;
    }

    /**
     * Daftar soal dari naskah beku — TANPA bobot/poin, jenis, TP, kunci, pembahasan.
     * Wacana (soal cerita) ikut dibawa untuk pengelompokan di lembar.
     *
     * @return array<int, array{nomor:int, pertanyaan:string, opsi:array<int, array{label:string, teks:string}>, wacana:?array<string, mixed>}>
     */
    public function questions(LjkExam $exam, ?string $examBahasa = null): array
    {
        if (! $exam->paketSoal) {
            return [];
        }

        $examBahasa = $examBahasa ?? $this->examBahasa($exam);

        return $exam->paketSoal->items()
            ->with(['soal.options', 'soal.wacana'])
            ->orderBy('urutan')
            ->get()
            ->values()
            ->map(function ($item, int $index) use ($examBahasa) {
                $konten = $item->kontenFinal();
                $wacana = $konten['wacana'] ?? null;

                $opsi = collect($konten['opsi'] ?? [])->map(fn ($o) => [
                    'label' => (string) ($o['label'] ?? ''),
                    'teks' => (string) ($o['teks'] ?? ''),
                ])->values()->all();

                // Format lembar mengikuti BAHASA UJIAN (bukan per soal):
                // 'arab' → seluruh naskah RTL + huruf ا، ب، ج، د;
                // 'latin'/'gabungan' → LTR + huruf A, B, C, D.
                $bahasa = $examBahasa;
                $isArabic = $bahasa === 'arab';
                $rtl = $isArabic;

                // Label tampil: huruf Arab untuk mode arab, label asli untuk lainnya.
                $huruf = (array) config('ljk.soal.huruf_arab', ['ا', 'ب', 'ج', 'د', 'هـ']);
                foreach ($opsi as $i => &$option) {
                    $option['display_label'] = $isArabic
                        ? ($huruf[$i] ?? (string) ($option['label'] ?? ''))
                        : (string) ($option['label'] ?? '');
                }
                unset($option);

                return [
                    'nomor' => (int) ($konten['nomor'] ?? $index + 1),
                    'pertanyaan' => (string) ($konten['pertanyaan'] ?? ''),
                    'opsi' => $opsi,
                    'bahasa' => $bahasa,
                    'isArabic' => $isArabic,
                    'rtl' => $rtl,
                    'wacana' => is_array($wacana) && ! empty($wacana['teks']) ? $wacana : null,
                ];
            })
            ->all();
    }

    /**
     * Susun blok cetak: wacana (sekali per kelompok berurutan) lalu butir soalnya.
     *
     * @param  array<int, array<string, mixed>>  $questions
     * @return array<int, array<string, mixed>>
     */
    public function blocks(array $questions): array
    {
        $blocks = [];
        $currentWacanaId = null;
        $runStart = null;
        $runEnd = null;
        $runIndex = null;

        foreach ($questions as $index => $q) {
            $wacana = $q['wacana'];
            $wacanaId = $wacana['id'] ?? null;
            $nomor = (int) $q['nomor'];

            // Tutup kelompok wacana sebelumnya.
            if ($wacanaId !== $currentWacanaId) {
                if ($currentWacanaId !== null && $runIndex !== null) {
                    $blocks[$runIndex]['dari'] = $runStart;
                    $blocks[$runIndex]['sampai'] = $runEnd;
                }

                $currentWacanaId = $wacanaId;
                $runStart = null;
                $runEnd = null;
                $runIndex = null;

                if ($wacanaId !== null) {
                    $blocks[] = [
                        'type' => 'wacana',
                        'judul' => $wacana['judul'] ?? null,
                        'teks' => (string) $wacana['teks'],
                        'sumber' => $wacana['sumber'] ?? null,
                        'rtl' => LjkArabicText::hasArabic($wacana['teks']) && ! preg_match('/[A-Za-z]/', (string) $wacana['teks']),
                        'dari' => null,
                        'sampai' => null,
                    ];
                    $runIndex = array_key_last($blocks);
                }
            }

            if ($currentWacanaId !== null) {
                $runStart ??= $nomor;
                $runEnd = $nomor;
            }

            $blocks[] = ['type' => 'soal'] + $q;
        }

        if ($currentWacanaId !== null && $runIndex !== null) {
            $blocks[$runIndex]['dari'] = $runStart;
            $blocks[$runIndex]['sampai'] = $runEnd;
        }

        return $blocks;
    }

    /**
     * Rentang nomor per wacana (untuk legenda lembar jawaban).
     *
     * @return array<int, string> mis. ["20–25", "26–30"]
     */
    public function wacanaRanges(LjkExam $exam): array
    {
        $ranges = $this->rangesFromBlocks($this->blocks($this->questions($exam)));

        if ($this->examBahasa($exam) !== 'arab' || (string) config('ljk.soal.angka_arab', 'auto') !== 'auto') {
            return $ranges;
        }

        // Angka rentang wacana juga memakai digit Arab pada naskah Arab.
        return array_map(fn (string $range) => strtr($range, [
            '0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤',
            '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩',
        ]), $ranges);
    }

    /**
     * @return array<int, string> [judul baris 1, "TAHUN AJARAN …"]
     */
    public function judul(LjkExam $exam): array
    {
        $map = (array) config('ljk.soal.judul', []);
        $jenis = (string) $exam->jenis_ujian;

        $judul = $map[$jenis] ?? 'SOAL ASESMEN';

        if (in_array($jenis, (array) config('ljk.soal.semester_pada_judul', ['sts', 'sas']), true)) {
            $judul .= ' '.mb_strtoupper((string) $exam->semester);
        }

        $tahun = trim((string) ($exam->academicYear?->name ?? ''));

        return [
            $judul,
            $tahun !== '' ? 'TAHUN AJARAN '.$tahun : '',
        ];
    }

    /**
     * Baris tabel identitas (maks 3 baris × 2 pasang label/nilai).
     * Untuk lembar jawaban, nama & nomor peserta diisi bila peserta diberikan.
     *
     * @return array<int, array<int, array{0: string, 1: string}>>
     */
    public function identityRows(LjkExam $exam, ?LjkParticipant $participant = null): array
    {
        $nama = '';
        $nis = '';

        if ($participant) {
            $nama = trim((string) ($participant->student?->name ?? ''));
            $nis = trim((string) ($participant->student?->nis ?? ''));
            $nama = $nama !== '' ? $nama : '-';
            $nis = $nis !== '' ? $nis : '-';
        }

        $rows = array_chunk([
            ['Mata Pelajaran', (string) ($exam->subject?->name ?? '-')],
            ['Hari/Tanggal', $this->dayDate($exam->tanggal) ?: '-'],
            ['Kelas', $this->kelasLabel($exam)],
            ['NIS', $nis],
            ['Guru Pengampu', (string) ($exam->adminBook?->teacher?->name ?? '-')],
            ['Nama Peserta Didik', $nama],
        ], 2);

        return array_slice($rows, 0, max(1, (int) config('ljk.soal.identitas_baris_maks', 3)));
    }

    /**
     * Judul lembar jawaban: baris 1 judul LJK, baris 2 tahun ajaran.
     *
     * @return array{0: string, 1: string}
     */
    public function judulLjk(LjkExam $exam): array
    {
        return [
            'LEMBAR JAWABAN KOMPUTER (LJK)',
            $this->judul($exam)[1] ?? '',
        ];
    }

    /**
     * "Jum'at, 18 September 2026" (bulan Indonesia).
     */
    public function dayDate(?Carbon $date): string
    {
        if (! $date) {
            return '';
        }

        $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu'][$date->dayOfWeek] ?? '';
        $bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ][$date->month] ?? '';

        return trim($hari.', '.$date->day.' '.$bulan.' '.$date->year);
    }

    /**
     * "Sepuluh (X) C & D" — dari tingkat + nama rombel.
     */
    public function kelasLabel(LjkExam $exam): string
    {
        $group = $exam->studyGroup;
        $grade = $group?->gradeLevel;

        $level = $this->gradeLevelNumber($grade?->level, (string) ($grade?->name ?? ''));
        $nama = trim((string) ($group?->name ?? ''));

        if ($level === null) {
            return $nama !== '' ? $nama : '-';
        }

        $kata = $this->numberWord($level);
        $romawi = $this->roman($level);

        return trim($kata.' ('.$romawi.')'.($nama !== '' ? ' '.$nama : ''));
    }

    /**
     * "07.30 – 09.00" (gaya Indonesia) atau '' bila jam belum diisi.
     */
    public function waktuLabel(LjkExam $exam): string
    {
        $mulai = $exam->waktu_mulai?->format('H.i');
        $selesai = $exam->waktu_selesai?->format('H.i');

        return match (true) {
            $mulai !== null && $selesai !== null => $mulai.' – '.$selesai,
            $mulai !== null => $mulai,
            default => '',
        };
    }

    private function gradeLevelNumber(?int $level, string $name): ?int
    {
        if ($level !== null && $level > 0) {
            return $level;
        }

        if (preg_match('/(\d{1,2})/', $name, $m)) {
            return (int) $m[1];
        }

        if (preg_match('/\b(X{0,3})(IX|IV|V?I{0,3})\b/i', $name, $m) && ($m[1].$m[2]) !== '') {
            $roman = strtoupper($m[1].$m[2]);
            $map = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5, 'VI' => 6,
                'VII' => 7, 'VIII' => 8, 'IX' => 9, 'X' => 10, 'XI' => 11, 'XII' => 12];

            return $map[$roman] ?? null;
        }

        return null;
    }

    private function numberWord(int $level): string
    {
        $words = [
            1 => 'Satu', 2 => 'Dua', 3 => 'Tiga', 4 => 'Empat', 5 => 'Lima', 6 => 'Enam',
            7 => 'Tujuh', 8 => 'Delapan', 9 => 'Sembilan', 10 => 'Sepuluh', 11 => 'Sebelas', 12 => 'Dua Belas',
        ];

        return $words[$level] ?? (string) $level;
    }

    private function roman(int $level): string
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        return $map[$level] ?? (string) $level;
    }
}
