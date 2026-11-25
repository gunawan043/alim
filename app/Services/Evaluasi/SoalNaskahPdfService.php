<?php

declare(strict_types=1);

namespace App\Services\Evaluasi;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Soal;
use App\Models\User;
use App\Services\ArabicName\ArabicNameResolver;
use App\Services\ArabicName\Contracts\ArabicNameGeneratorContract;
use App\Services\Ljk\LjkArabicText;
use App\Services\Ljk\LjkPdfFonts;
use App\Services\Ljk\LjkSchoolLetterhead;
use App\Services\Ljk\LjkSoalSheet;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Pharaonic\Hijri\Hijri;

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
        $data = $this->data($questions, $school, $user, $settings, $judul, $kelasLabel);
        $view = ($data['examBahasa'] ?? 'latin') === Soal::BAHASA_ARAB
            ? 'ljk.arab.soal-pdf'
            : 'ljk.soal-pdf';

        return view($view, $data)->render();
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
                    // Label tampil DITENTUKAN OLEH BAHASA NASKAH (format baku
                    // LJK), bukan oleh bahasa soal — lihat resolveExamBahasa().
                    'display_label' => (string) $option->label,
                ])
                ->all();

            $wacana = $item->wacana;

            // Bahasa per soal hanya dipakai untuk memilih mode naskah saat
            // setelan 'bahasa' = otomatis. Keputusan RTL/label ada di tingkat
            // dokumen (lihat blok "Mode bahasa naskah" di bawah).
            $bahasa = $item->bahasaEfektif();
            $isArabic = $bahasa === Soal::BAHASA_ARAB;

            return [
                'nomor' => $index + $startNumber,
                'pertanyaan' => (string) $item->pertanyaan,
                'opsi' => $opsi,
                'bahasa' => $bahasa,
                'isArabic' => $isArabic,
                'rtl' => $isArabic,
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

        // ── Mode bahasa naskah (format baku LJK) ──────────────────────────
        // Bahasa adalah keputusan TINGKAT DOKUMEN, bukan per soal — sama
        // persis seperti LjkSoalSheet::examBahasa():
        //   'arab'  → seluruh naskah RTL, huruf ا/ب/ج/د, nomor ١، ٢، ٣
        //   'latin' → LTR, huruf A/B/C/D, nomor 1, 2, 3
        // Setelan 'bahasa' = otomatis memilih 'arab' bila mayoritas soal
        // berbahasa Arab; guru dapat memaksanya lewat setelan cetak.
        $examBahasa = $this->resolveExamBahasa($settings, $soal);
        $isArabicExam = $examBahasa === Soal::BAHASA_ARAB;
        $arabAngka = $isArabicExam && (string) config('ljk.soal.angka_arab', 'auto') !== 'off';

        // Label opsi WAJIB mengikuti bahasa naskah: pada naskah Arab semua
        // butir memakai ا/ب/ج/د, pada naskah Latin semua butir memakai
        // A/B/C/D — termasuk soal yang bahasanya berbeda dari naskahnya.
        $huruf = (array) config('ljk.soal.huruf_arab', ['ا', 'ب', 'ج', 'د', 'هـ']);

        foreach ($soal as &$butir) {
            $butir['bahasa'] = $examBahasa;
            $butir['isArabic'] = $isArabicExam;
            $butir['rtl'] = $isArabicExam;

            foreach ($butir['opsi'] as $i => &$option) {
                $option['display_label'] = $isArabicExam
                    ? ($huruf[$i] ?? (string) ($option['label'] ?? ''))
                    : (string) ($option['label'] ?? '');
            }
            unset($option);
        }
        unset($butir);

        // Blok dibangun ulang dari $soal agar rtl/label ikut berubah.
        $blocks = $sheet->blocks($soal);

        foreach ($blocks as &$block) {
            if ($block['type'] !== 'soal') {
                continue;
            }

            $block['rtl'] = $isArabicExam;
            $block['bahasa'] = $examBahasa;
            $block['nomor_tampil'] = $arabAngka
                ? $sheet->formatNumber((int) $block['nomor'], true)
                : (string) $block['nomor'].'.';
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

        // Lebar PASTI area opsi (mm). Dipakai juga di view agar lebar tabel
        // opsi identik pada semua soal — bila dibiarkan 100% dari kolom
        // qbody, lebar tiap soal sedikit berbeda (nomor 1. vs 10.) sehingga
        // posisi kolom A/B/C tidak sejajar antar soal.
        $opsiWidthMm = round($contentMm, 2);

        // Grid kolom DIPAKU untuk seluruh naskah. Tanpa ini setiap soal
        // menghitung kolomnya sendiri dari panjang opsinya, sehingga soal
        // dengan opsi pendek memakai 4 kolom dan soal lain 2 atau 1 kolom —
        // penanda A/B/C/D jadi tidak berada di satu garis vertikal.
        $kolomGrid = $layout->kolomGrid($maxOptions, $maxColumns);

        foreach ($blocks as &$block) {
            if ($block['type'] !== 'soal' || empty($block['opsi'])) {
                continue;
            }

            $layoutResult = $layout->layout(
                $block['opsi'],
                $contentMm,
                (float) ($settings['font_size'] ?? 11),
                $maxColumns,
                $kolomGrid,
                (float) ($settings['option_indent'] ?? 2)
            );

            // Naskah RTL: pilihan pertama (ا) harus di kolom PALING KANAN.
            // Semua tabel opsi dibangun kiri-ke-kanan, jadi urutan sel
            // dibalik per baris agar ا, ب, ج, د terbaca kanan-ke-kiri.
            if ($isArabicExam) {
                foreach ($layoutResult['rows'] as &$row) {
                    $row = array_reverse($row);
                }
                unset($row);
            }

            $block['opsiLayout'] = $layoutResult;
        }
        unset($block);

        $first = $questions->first();
        $academicYear = $first?->bankSoal?->academicYear
            ?? AcademicYear::query()->where('is_active', true)->first();

        $print = app(PrintSettingsService::class);

        $judulLines = $this->judulLines($judul, $academicYear, $settings, $isArabicExam);

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

        // Nama mapel & guru diambil dari kolom Arab data master
        // (`subjects.name_ar`, `users.name_ar`) karena data itu sudah ada di
        // kurikulum/kepegawaian. Bila belum diisi, dipakai nama Latin agar
        // naskah tidak kosong — guru melengkapinya di halaman master.
        $subjectMaster = $first?->bankSoal?->subject;
        $mapel = (string) ($subjectMaster?->name ?? '-');
        $kelas = $kelasLabel ?: (string) ($first?->bankSoal?->gradeLevel?->name ?? '-');

        // Naskah Arab: nama mapel & guru WAJIB Arab. Utamakan kolom name_ar
        // dari master kurikulum/GTK, lalu kamus mapel, lalu transliterasi
        // otomatis. Naskah Latin tetap memakai nama aslinya.
        $mapelTampil = $mapel;
        $guruTampil = (string) $user->name;

        if ($isArabicExam) {
            $arabicNames = app(ArabicNameResolver::class);
            $generator = app(ArabicNameGeneratorContract::class);

            $mapelTampil = (string) ($arabicNames->subjectName($subjectMaster)
                ?? $generator->generate($mapel)
                ?? $mapel);
            $guruTampil = (string) (filled($user->name_ar)
                ? $user->name_ar
                : ($arabicNames->userName($user) ?? $generator->generate((string) $user->name)));
        }

        $identityRows = $this->identityRows($settings, [
            'mapel' => $mapelTampil,
            'kelas' => $kelas,
            'guru' => $guruTampil,
        ], $isArabicExam, $this->tanggalUjian($settings, $isArabicExam));

        $karakter = ! ($settings['show_karakter'] ?? true) ? [] : $print->karakterItems($settings);

        // Naskah Arab memakai penanaman karakter berbahasa Arab bila guru
        // belum mengisi butir sendiri (format baku: seluruh informasi Arab).
        if ($isArabicExam && trim((string) ($settings['karakter_items'] ?? '')) === '') {
            $karakter = (array) config('ljk.soal.penanaman_karakter_arab', []);
        }

        return [
            'judul' => $judulLines,
            // Tanggal dicetak di bawah judul HANYA pada naskah Arab, memakai
            // Hijriah + Masehi (bulan berbahasa Arab).
            'tanggalArab' => $isArabicExam ? $this->tanggalArab() : '',
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
                'title' => $this->karakterTitle($settings, $isArabicExam),
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
            'opsiWidthMm' => $opsiWidthMm,
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
            'mataPelajaran' => $mapelTampil,
            'kelas' => $kelas,
            'guruPengampu' => $guruTampil,
            'namaPeserta' => '',
            'karakter' => $karakter,
            'instruksi' => ($settings['show_instruksi'] ?? true) ? $print->instruksi($settings) : '',
            'instruksiArab' => $isArabicExam ? (string) config('ljk.soal.instruksi_arab', '') : '',
            'soal' => $soal,
            'blocks' => $blocks,
            'examBahasa' => $examBahasa,
            'arabAngka' => $arabAngka,
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
     * Judul blok penanaman karakter. Pada naskah Arab dipakai judul Arab,
     * kecuali guru mengetik judul sendiri (bukan bawaan Latin).
     *
     * @param  array<string, mixed>  $settings
     */
    private function karakterTitle(array $settings, bool $isArabic): string
    {
        $default = 'PENANAMAN KARAKTER :';
        $judul = trim((string) ($settings['karakter_title'] ?? ''));

        if ($isArabic && ($judul === '' || $judul === $default)) {
            return (string) config('ljk.soal.karakter_title_arab', 'تنمية الشخصية :');
        }

        return $judul !== '' ? $judul : $default;
    }

    /**
     * Bahasa naskah: setelan guru bila dipaksa, atau auto dari mayoritas soal.
     *
     * @param  array<string, mixed>  $settings
     * @param  array<int, array<string, mixed>>  $soal
     */
    private function resolveExamBahasa(array $settings, array $soal): string
    {
        $pilihan = (string) ($settings['bahasa'] ?? 'otomatis');

        if ($pilihan === 'arab' || $pilihan === 'latin') {
            return $pilihan;
        }

        $jumlahArab = collect($soal)->filter(fn ($q) => $q['isArabic'] ?? false)->count();

        return ($soal !== [] && $jumlahArab * 2 > count($soal))
            ? Soal::BAHASA_ARAB
            : Soal::BAHASA_LATIN;
    }

    /**
     * Baris judul: [judul naskah, baris tahun ajaran].
     *
     * @param  array<string, mixed>  $settings
     * @return array{0: string, 1: string}
     */
    private function judulLines(?string $judul, ?AcademicYear $academicYear, array $settings, bool $isArabic = false): array
    {
        $line1 = trim((string) $judul);

        if ($line1 === '') {
            $line1 = trim((string) ($settings['judul'] ?? ''));
        }

        if ($line1 === '') {
            $line1 = $isArabic ? 'أسئلة الاختبار' : 'NASKAH SOAL';
        }

        if (! ($settings['show_tahun_ajaran'] ?? true)) {
            return [$line1, ''];
        }

        $tahun = trim((string) ($settings['tahun_ajaran'] ?? ''));

        if ($tahun === '') {
            $tahun = trim((string) ($academicYear?->name ?? ''));
        }

        if ($isArabic) {
            return [$line1, $this->tahunAjaranArab($tahun)];
        }

        return [$line1, $tahun !== '' ? (str_starts_with(mb_strtoupper($tahun), 'TAHUN AJARAN') ? $tahun : 'TAHUN AJARAN '.$tahun) : ''];
    }

    /**
     * Baris tahun ajaran versi Arab: "السنة الدراسية ٢٠٢٦/٢٠٢٧ مـ".
     */
    private function tahunAjaranArab(string $tahun): string
    {
        $tahun = preg_replace('/^TAHUN\s+AJARAN\s*/iu', '', $tahun) ?? $tahun;
        $tahun = trim($tahun);

        if ($tahun === '') {
            return '';
        }

        return 'السنة الدراسية '.$this->angkaArab($tahun).' مـ';
    }

    /**
     * Ubah digit Latin pada teks menjadi digit Arab (١٢٣), tanpa menyentuh
     * huruf atau tanda baca.
     */
    private function angkaArab(string $teks): string
    {
        return strtr($teks, [
            '0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤',
            '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩',
        ]);
    }

    /**
     * Tanggal naskah Arab: Hijriah lalu Masehi, keduanya dengan bulan Arab.
     * Contoh: "٢٤ جمادى الآخرة ١٤٤٨ هـ / ٢٤ نوفمبر ٢٠٢٦ م".
     */
    public function tanggalArab(?string $isoDate = null): string
    {
        $tanggal = $isoDate ? Carbon::parse($isoDate) : now();

        $bulanMasehi = [
            1 => 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
            'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر',
        ];

        $masehi = $this->angkaArab(
            $tanggal->day.' '.$bulanMasehi[(int) $tanggal->month].' '.$tanggal->year
        ).' م';

        // Hijriah: pakai library pharaonic/hijri yang sudah dipakai modul
        // kesiswaan. Bila gagal, cukup tampilkan Masehi.
        $bulanHijri = [
            1 => 'محرم', 'صفر', 'ربيع الأول', 'ربيع الآخر',
            'جمادى الأولى', 'جمادى الآخرة', 'رجب', 'شعبان',
            'رمضان', 'شوال', 'ذو القعدة', 'ذو الحجة',
        ];

        try {
            Hijri::getInstance();
            $h = Hijri::parse($tanggal->toDateString());

            $hijri = $this->angkaArab($h->day.' '.$bulanHijri[(int) $h->month].' '.$h->year).' هـ';

            return $hijri.' / '.$masehi;
        } catch (\Throwable $e) {
            return $masehi;
        }
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
    private function identityRows(array $settings, array $values, bool $isArabic = false, ?string $tanggalUjian = null): array
    {
        if (! ($settings['show_identitas'] ?? true)) {
            return [];
        }

        $override = function (string $field, string $fallback) use ($settings): string {
            $value = trim((string) ($settings['identitas_isi_'.$field] ?? ''));

            return $value !== '' ? $value : $fallback;
        };

        // Mode Arab memakai label Arab untuk SEMUA kolom, kecuali guru
        // sengaja mengetik label sendiri.
        $arab = (array) config('ljk.soal.identitas_arab', []);
        $label = function (string $field, string $latin) use ($settings, $isArabic, $arab): string {
            $custom = trim((string) ($settings['identitas_label_'.$field] ?? ''));

            // Label bawaan di setelan sama dengan versi Latin; anggap belum
            // diubah sehingga label Arab boleh dipakai.
            if ($isArabic && ($custom === '' || $custom === $latin)) {
                return (string) ($arab[$field] ?? $latin);
            }

            return $custom !== '' ? $custom : $latin;
        };

        $rows = [
            [
                [$label('mapel', 'Mata Pelajaran'), $override('mapel', $values['mapel'])],
                [$label('kelas', 'Kelas'), $override('kelas', $values['kelas'])],
            ],
        ];

        if ((int) ($settings['identitas_rows'] ?? 2) >= 2) {
            // Kolom kanan baris kedua bisa berupa garis isi nama peserta atau
            // tanggal ujian, sesuai setelan guru.
            $kolomKanan = (string) ($settings['identitas_kolom_kanan'] ?? 'peserta');

            if ($kolomKanan === 'tanggal') {
                $rows[] = [
                    [$label('guru', 'Guru Pengampu'), $override('guru', $values['guru'])],
                    [$label('tanggal', 'Tanggal Ujian'), (string) $tanggalUjian],
                ];
            } else {
                $rows[] = [
                    [$label('guru', 'Guru Pengampu'), $override('guru', $values['guru'])],
                    [$label('peserta', 'Nama Peserta Didik'), ''],
                ];
            }
        }

        return $rows;
    }

    /**
     * Tanggal ujian tercetak pada tabel identitas.
     *
     * Sumber: setelan `identitas_tanggal` (Y-m-d). Bila kosong dipakai tanggal
     * cetak hari ini. Naskah Arab memakai `tanggalArab()` (Hijriah + Masehi
     * berbahasa Arab); naskah Latin memakai format Indonesia.
     */
    private function tanggalUjian(array $settings, bool $isArabic = false): string
    {
        $raw = trim((string) ($settings['identitas_tanggal'] ?? ''));
        $iso = $raw !== '' ? $raw : null;

        if ($isArabic) {
            return $this->tanggalArab($iso);
        }

        return ($iso !== null ? Carbon::parse($iso) : now())
            ->locale('id')
            ->translatedFormat('j F Y');
    }
}
