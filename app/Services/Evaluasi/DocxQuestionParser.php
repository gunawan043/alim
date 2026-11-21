<?php

namespace App\Services\Evaluasi;

use ZipArchive;

/**
 * Pembaca soal dari file .docx tanpa dependensi PHPWord.
 *
 * Format yang dikenali:
 *   1. Pertanyaan ...
 *   A. Opsi ...
 *   B. Opsi ...
 *   Kunci: A
 *   Pembahasan: ...
 *
 * Blok "KUNCI JAWABAN" di akhir dokumen juga didukung:
 *   1. A
 *   2. B
 */
class DocxQuestionParser
{
    /**
     * @return array{questions: array<int, array<string, mixed>>, warnings: array<int, string>}
     */
    public function parse(string $path): array
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
            'questions' => $this->parseParagraphs($paragraphs, $warnings),
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
     * @return array<int, array<string, mixed>>
     */
    private function parseParagraphs(array $paragraphs, array &$warnings): array
    {
        $questions = [];
        $current = null;
        $keySection = false;
        $keys = [];
        $lastField = null;

        $startQuestion = function (int $number, string $text) use (&$current, &$questions): void {
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
                if (preg_match('/^(\d+)\s*[\.\)\-:]?\s*([A-Ea-e])\b/', $line, $m)) {
                    $keys[(int) $m[1]] = strtoupper($m[2]);
                }

                continue;
            }

            if (preg_match('/^(?:soal\s*)?(\d+)\s*[\.\)]\s*(.+)$/i', $line, $m)) {
                $keySection = false;
                $startQuestion((int) $m[1], $m[2]);
                $lastField = 'pertanyaan';

                continue;
            }

            if ($current !== null && preg_match('/^([A-Ea-e])\s*[\.\)]\s*(.+)$/u', $line, $m)) {
                $current['options'][] = [
                    'label' => strtoupper($m[1]),
                    'teks_opsi' => trim($m[2]),
                ];
                $current['tipe_soal'] = 'pg';
                $lastField = 'option:'.(count($current['options']) - 1);

                continue;
            }

            if ($current !== null && preg_match('/^kunci(?:\s*jawaban)?\s*[:\-]\s*([A-Ea-e])\b/i', $line, $m)) {
                $current['correct_label'] = strtoupper($m[1]);
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

            if ($question['tipe_soal'] === 'pg' && ! $question['correct_label']) {
                $warnings[] = "Soal nomor {$number} belum memiliki kunci jawaban.";
            }

            $labels = array_column($question['options'], 'label');
            if ($question['correct_label'] && ! in_array($question['correct_label'], $labels, true)) {
                $warnings[] = "Kunci {$question['correct_label']} pada soal nomor {$number} tidak ada di opsi; kunci dikosongkan.";
                $question['correct_label'] = null;
            }

            $valid[] = $question;
        }

        if ($valid === []) {
            $warnings[] = 'Tidak ada soal bernomor yang terdeteksi. Gunakan format "1. Pertanyaan" dan opsi "A. ...".';
        }

        return $valid;
    }
}
