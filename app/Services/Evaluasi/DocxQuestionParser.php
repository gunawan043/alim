<?php

namespace App\Services\Evaluasi;

use ZipArchive;

/**
 * Pembaca soal dari file .docx tanpa dependensi PHPWord.
 *
 * Format yang dikenali:
 *   1. Pertanyaan ...
 *   A. Opsi ...            (latin)
 *   ا. Opsi ...            (arab, RTL)
 *   Kunci: A  /  Kunci: ا
 *   Pembahasan: ...
 *
 * Wacana (teks bacaan yang dipakai beberapa soal sekaligus) dikenali dari
 * baris yang tidak bernomor dan muncul SEBELUM soal pertama, atau didahului
 * penanda "Wacana:", "Bacaan:", atau "Teks berikut:". Wacana berlaku sampai
 * penanda wacana berikutnya.
 *
 * Blok "KUNCI JAWABAN" di akhir dokumen juga didukung:
 *   1. A
 *   2. B
 */
class DocxQuestionParser
{
    /** Label opsi latin (A-E) → huruf kanonik. */
    private const LATIN_LABELS = ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D', 'e' => 'E'];

    /** Label opsi Arab (alif..ha) → huruf kanonik latin. */
    private const ARAB_LABELS = ['ا' => 'A', 'ب' => 'B', 'ج' => 'C', 'د' => 'D', 'هـ' => 'E', 'ه' => 'E'];

    /** Penanda awal wacana. */
    private const WACANA_MARKER = '/^(?:wacana|bacaan|teks\s+berikut|stimulus)\s*[:\-]\s*(.*)$/iu';

    /**
     * @param  array{jenis_soal?: string, bahasa?: string}  $options
     *                                                                jenis_soal: pg|uraian|isian|campuran (campuran = ikuti isi dokumen)
     *                                                                bahasa: latin|arab|otomatis (otomatis = deteksi per soal)
     * @return array{questions: array<int, array<string, mixed>>, warnings: array<int, string>}
     */
    public function parse(string $path, array $options = []): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new \RuntimeException('Ekstensi ZIP PHP tidak tersedia untuk membaca file .docx.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('File .docx tidak dapat dibuka.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false || $xml === '') {
            throw new \RuntimeException('Isi dokumen Word tidak ditemukan.');
        }

        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new \RuntimeException('Struktur XML dokumen Word tidak valid.');
        }

        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $paragraphs = [];
        foreach ($xpath->query('//w:p') ?: [] as $paragraph) {
            $text = $this->paragraphText($paragraph, $xpath);
            if (trim($text) !== '') {
                $paragraphs[] = trim($text);
            }
        }

        $warnings = [];
        if (str_contains($xml, '<w:drawing') || str_contains($xml, '<w:pict')) {
            $warnings[] = 'Dokumen memuat gambar. Gambar tidak diimpor; tambahkan gambar secara manual bila diperlukan.';
        }

        return [
            'questions' => $this->parseParagraphs($paragraphs, $warnings, $options),
            'warnings' => $warnings,
        ];
    }

    private function paragraphText(\DOMNode $paragraph, \DOMXPath $xpath): string
    {
        $text = '';

        foreach ($xpath->query('.//w:t | .//w:tab | .//w:br', $paragraph) ?: [] as $node) {
            if ($node->localName === 'tab') {
                $text .= ' ';
            } elseif ($node->localName === 'br') {
                $text .= "\n";
            } else {
                $text .= $node->nodeValue;
            }
        }

        return $text;
    }

    /**
     * @param  array<int, string>  $paragraphs
     * @param  array<int, string>  $warnings
     * @param  array{jenis_soal?: string, bahasa?: string}  $options
     * @return array<int, array<string, mixed>>
     */
    private function parseParagraphs(array $paragraphs, array &$warnings, array $options = []): array
    {
        $questions = [];
        $current = null;
        $keySection = false;
        $keys = [];
        $lastField = null;
        $wacana = null;
        $wacanaBuffer = null;

        $startQuestion = function (int $number, string $text) use (&$current, &$questions, &$wacana): void {
            if ($current !== null) {
                $questions[] = $current;
            }

            $current = [
                'number' => $number,
                'pertanyaan' => trim($text),
                'options' => [],
                'correct_label' => null,
                'pembahasan' => null,
                'tipe_soal' => 'uraian',
                'wacana' => $wacana,
            ];
        };

        foreach ($paragraphs as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^kunci\s*(jawaban)?\s*[:\-]?\s*$/i', $line)) {
                $keySection = true;
                $lastField = null;

                continue;
            }

            if ($keySection) {
                if (preg_match('/^(\d+)\s*[\.\)\-:]?\s*([A-Ea-e]|[ابجد]|هـ|ه)(?![A-Za-z0-9\x{0600}-\x{06FF}])/iu', $line, $m)) {
                    $keys[(int) $m[1]] = $this->canonicalLabel($m[2]);
                }

                continue;
            }

            // Penanda wacana eksplisit: "Wacana: ..." / "Bacaan: ..."
            if (preg_match(self::WACANA_MARKER, $line, $m)) {
                // Tutup soal sebelumnya agar wacana baru tidak menempel ke soal lama.
                if ($current !== null) {
                    $questions[] = $current;
                    $current = null;
                }

                $wacana = trim($m[1]);
                $wacanaBuffer = $wacana === '' ? [] : null;
                $lastField = null;

                continue;
            }

            if (preg_match('/^(?:soal\s*)?([\p{Nd}]+)\s*[\.\)]\s*(.+)$/iu', $line, $m)) {
                $keySection = false;
                $startQuestion($this->toInt($m[1]), $m[2]);
                $lastField = 'pertanyaan';

                continue;
            }

            [$label, $optionText] = $this->matchOption($line);

            if ($current !== null && $label !== null) {
                $current['options'][] = [
                    'label' => $label,
                    'teks_opsi' => $optionText,
                ];
                $current['tipe_soal'] = 'pg';
                $lastField = 'option:'.(count($current['options']) - 1);

                continue;
            }

            if ($current !== null && preg_match('/^kunci(?:\s*jawaban)?\s*[:\-]\s*([A-Ea-e]|[ابجد]|هـ|ه)(?![A-Za-z0-9\x{0600}-\x{06FF}])/iu', $line, $m)) {
                $current['correct_label'] = $this->canonicalLabel($m[1]);
                $lastField = null;

                continue;
            }

            if ($current !== null && preg_match('/^pembahasan\s*[:\-]\s*(.*)$/i', $line, $m)) {
                $current['pembahasan'] = trim($m[1]);
                $lastField = 'pembahasan';

                continue;
            }

            if ($current !== null && preg_match('/^(jenis|tipe)\s*(soal)?\s*[:\-]\s*(pg|pilihan ganda|uraian|esai|isian)\b/i', $line, $m)) {
                $value = mb_strtolower(trim($m[3]));
                $current['tipe_soal'] = in_array($value, ['pg', 'pilihan ganda'], true) ? 'pg' : ($value === 'isian' ? 'isian' : 'uraian');
                $lastField = null;

                continue;
            }

            if ($current === null) {
                // Teks bebas sebelum soal pertama = calon wacana.
                if ($wacana === null || $wacanaBuffer !== null) {
                    $wacanaBuffer ??= [];
                    $wacanaBuffer[] = $line;
                    $wacana = trim(implode(' ', $wacanaBuffer));
                }

                continue;
            }

            // Baris lanjutan: tempelkan ke field terakhir yang sedang aktif.
            if ($lastField === 'pembahasan') {
                $current['pembahasan'] = trim((string) $current['pembahasan'].' '.$line);
            } elseif (is_string($lastField) && str_starts_with($lastField, 'option:')) {
                $index = (int) substr($lastField, 7);
                $current['options'][$index]['teks_opsi'] = trim($current['options'][$index]['teks_opsi'].' '.$line);
            } else {
                $current['pertanyaan'] = trim($current['pertanyaan'].' '.$line);
            }
        }

        if ($current !== null) {
            $questions[] = $current;
        }

        // Terapkan kunci dari blok KUNCI JAWABAN di akhir.
        if ($keys !== []) {
            foreach ($questions as &$question) {
                if (! $question['correct_label'] && isset($keys[$question['number']])) {
                    $question['correct_label'] = $keys[$question['number']];
                }
            }
            unset($question);
        }

        $forcedJenis = (string) ($options['jenis_soal'] ?? 'campuran');
        $forcedBahasa = (string) ($options['bahasa'] ?? 'otomatis');

        $valid = [];
        foreach ($questions as $index => $question) {
            $number = (int) ($question['number'] ?? ($index + 1));
            $text = trim((string) ($question['pertanyaan'] ?? ''));

            if ($text === '') {
                $warnings[] = "Soal nomor {$number} tidak memiliki teks dan dilewati.";

                continue;
            }

            if ($question['tipe_soal'] === 'pg' && count($question['options']) < 2) {
                $warnings[] = "Soal nomor {$number} terdeteksi PG tetapi opsinya kurang dari 2; disimpan sebagai uraian.";
                $question['tipe_soal'] = 'uraian';
                $question['options'] = [];
                $question['correct_label'] = null;
            }

            // Pilihan jenis soal dari form menimpa tebakan parser.
            if (in_array($forcedJenis, ['pg', 'uraian', 'isian'], true)) {
                if ($forcedJenis !== 'pg' && $question['options'] !== []) {
                    $warnings[] = "Soal nomor {$number} diimpor sebagai {$forcedJenis}; {$this->countOptions($question)} opsi diabaikan.";
                    $question['options'] = [];
                    $question['correct_label'] = null;
                }
                $question['tipe_soal'] = $forcedJenis;
            }

            if ($question['tipe_soal'] === 'pg' && ! $question['correct_label']) {
                $warnings[] = "Soal nomor {$number} belum memiliki kunci jawaban.";
            }

            $labels = array_column($question['options'], 'label');
            if ($question['correct_label'] && ! in_array($question['correct_label'], $labels, true)) {
                $warnings[] = "Kunci {$question['correct_label']} pada soal nomor {$number} tidak ada di opsi; kunci dikosongkan.";
                $question['correct_label'] = null;
            }

            // Bahasa: pilihan form menang, selain "otomatis" deteksi per soal.
            $question['bahasa'] = $forcedBahasa !== 'otomatis' && $forcedBahasa !== ''
                ? $forcedBahasa
                : $this->detectBahasa($question);

            $valid[] = $question;
        }

        if ($valid === []) {
            $warnings[] = 'Tidak ada soal bernomor yang terdeteksi. Gunakan format "1. Pertanyaan" dan opsi "A. ..." atau "ا. ...".';
        }

        return $valid;
    }

    /**
     * Cocokkan baris opsi, latin (A.) maupun Arab (ا. / ب.).
     *
     * @return array{0: ?string, 1: string} [label kanonik atau null, teks opsi]
     */
    private function matchOption(string $line): array
    {
        if (preg_match('/^([A-Ea-e])\s*[\.\)]\s*(.+)$/u', $line, $m)) {
            return [$this->canonicalLabel($m[1]), trim($m[2])];
        }

        // Label Arab diikuti titik/kurung (termasuk titik Arab ٫).
        if (preg_match('/^(هـ|ه|[ابجد])\s*[\.\)\x{066B}\x{066C}]\s*(.+)$/u', $line, $m)) {
            return [$this->canonicalLabel($m[1]), trim($m[2])];
        }

        return [null, ''];
    }

    /**
     * Ubah label apa pun (A/a/ا) ke huruf kanonik A-E.
     */
    private function canonicalLabel(string $label): string
    {
        $label = trim($label);

        return self::ARAB_LABELS[$label] ?? self::LATIN_LABELS[mb_strtolower($label)] ?? mb_strtoupper($label);
    }

    /**
     * Deteksi bahasa soal: arab bila mayoritas kuat huruf Arab.
     */
    private function detectBahasa(array $question): string
    {
        $teks = (string) ($question['pertanyaan'] ?? '');
        foreach ($question['options'] ?? [] as $option) {
            $teks .= ' '.$option['teks_opsi'];
        }

        $teks = preg_replace('/\s+/u', '', $teks) ?? '';
        if ($teks === '') {
            return 'latin';
        }

        $arab = preg_match_all('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FEFF}]/u', $teks);
        $total = mb_strlen($teks);

        return $total > 0 && ($arab / $total) >= 0.3 ? 'arab' : 'latin';
    }

    /**
     * Ubah angka apa pun (Arab ١٢٣ / Latin 123) ke int. `(int) '١'` bernilai
     * 0 karena bukan digit ASCII, sehingga nomor soal Arab hilang.
     */
    private function toInt(string $angka): int
    {
        $latin = preg_replace_callback('/[\x{0660}-\x{0669}\x{06F0}-\x{06F9}]/u', function ($m) {
            $code = mb_ord($m[0], 'UTF-8');

            // ٠..٩ = U+0660..U+0669, ۰..۹ = U+06F0..U+06F9
            return (string) ($code >= 0x06F0 ? $code - 0x06F0 : $code - 0x0660);
        }, $angka) ?? $angka;

        return (int) $latin;
    }

    private function countOptions(array $question): int
    {
        return count($question['options'] ?? []);
    }
}
