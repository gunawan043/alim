<?php

declare(strict_types=1);

namespace App\Services\Ljk;

use App\Models\LjkExam;
use App\Models\LjkParticipant;
use App\Models\LjkSheet;
use App\Services\Ljk\Omr\StripCodec;
use RuntimeException;

/**
 * Identitas lembar: kode unik, QR tertandatangan HMAC, data strip, dan PDF cetak.
 */
class LjkSheetService
{
    /**
     * Buat lembar baru untuk peserta (idempoten per peserta).
     */
    public function createSheet(LjkExam $exam, LjkParticipant $participant): LjkSheet
    {
        $existing = LjkSheet::query()->where('participant_id', $participant->id)->first();
        if ($existing) {
            return $existing;
        }

        $page = 1;
        $sheetCode = $this->sheetCode($exam, (int) $participant->nomor_peserta);

        $sheet = LjkSheet::create([
            'ljk_exam_id' => $exam->id,
            'participant_id' => $participant->id,
            'template_id' => $exam->template_id,
            'template_version' => (int) ($exam->template?->version ?? 1),
            'sheet_code' => $sheetCode,
            'qr_token' => '',
            'pages' => max(1, (int) ($exam->template?->pageCount() ?? 1)),
            'status' => LjkSheet::STATUS_GENERATED,
            'generated_at' => now(),
        ]);

        $sheet->forceFill([
            'qr_token' => $this->buildQrPayload($exam, $participant, $sheet, $page),
        ])->save();

        return $sheet;
    }

    public function sheetCode(LjkExam $exam, int $nomorPeserta): string
    {
        return $exam->code.'-'.str_pad((string) $nomorPeserta, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Payload QR: identitas lembar yang ditandatangani HMAC (tanpa kunci jawaban).
     */
    public function buildQrPayload(LjkExam $exam, LjkParticipant $participant, LjkSheet $sheet, int $page = 1): string
    {
        $signature = $this->signature($exam->id, $participant->id, $sheet->sheet_code, $page);

        return json_encode([
            'v' => (int) config('ljk.qr.version', 1),
            's' => $sheet->sheet_code,
            'e' => $exam->code,
            'n' => $participant->nomor_peserta,
            'p' => $page,
            'g' => $signature,
        ], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Verifikasi payload QR hasil pindai (web/app atau data strip).
     *
     * @return array{s:string,e:string,n:string,p:int}|null
     */
    public function verifyQrPayload(string $payload): ?array
    {
        $data = json_decode($payload, true);

        if (! is_array($data) || ! isset($data['s'], $data['e'], $data['n'], $data['p'], $data['g'])) {
            return null;
        }

        $sheet = LjkSheet::query()->where('sheet_code', (string) $data['s'])->first();
        if (! $sheet) {
            return null;
        }

        $expected = $this->signature($sheet->ljk_exam_id, $sheet->participant_id, $sheet->sheet_code, (int) $data['p']);
        if (! hash_equals($expected, (string) $data['g'])) {
            return null;
        }

        return [
            's' => (string) $data['s'],
            'e' => (string) $data['e'],
            'n' => (string) $data['n'],
            'p' => (int) $data['p'],
        ];
    }

    public function signature(string $examId, string $participantId, string $sheetCode, int $page): string
    {
        $bytes = (int) config('ljk.qr.signature_bytes', 10);
        $raw = hash_hmac('sha256', implode('|', [$examId, $participantId, $sheetCode, $page]), config('app.key'), true);

        return bin2hex(substr($raw, 0, $bytes));
    }

    /**
     * Bit data strip untuk peserta + halaman.
     *
     * @return array<int, int>
     */
    public function stripBits(LjkExam $exam, LjkParticipant $participant, int $page = 1): array
    {
        return StripCodec::encodeBits((int) $participant->nomor_peserta, $page);
    }

    /**
     * QR sebagai data-URI PNG (agar aman dirender Dompdf tanpa jaringan).
     */
    public function qrDataUri(LjkExam $exam, LjkParticipant $participant, int $page = 1): string
    {
        $sheet = $participant->sheet
            ?? LjkSheet::query()->where('participant_id', $participant->id)->firstOrFail();

        $payload = $this->buildQrPayload($exam, $participant, $sheet, $page);

        return LjkQrRenderer::pngDataUri($payload, 420);
    }

    /**
     * HTML lembar siap cetak (dirender ke PDF via Dompdf).
     *
     * @param  bool  $master  Mode "master fotokopi": identitas dikosongkan,
     *                        tanpa QR & data strip (untuk diperbanyak lalu
     *                        diisi manual oleh santri).
     */
    public function renderSheetHtml(LjkExam $exam, LjkParticipant $participant, int $page = 1, bool $master = false, ?string $masterNote = null): string
    {
        $exam->loadMissing(['school', 'subject', 'studyGroup', 'template']);
        $participant->loadMissing('student', 'sheet');
        $sheet = $participant->sheet;

        if (! $sheet) {
            throw new RuntimeException('Lembar belum diterbitkan untuk peserta ini.');
        }

        if (! $sheet->template || $sheet->template->version !== (int) $sheet->template_version) {
            throw new RuntimeException('Template lembar tidak cocok dengan versi yang tercatat.');
        }

        $template = $sheet->template;
        app(LjkTemplateService::class)->assertValidGeometry($template->geometry);

        // Kop lembaga (tertaut pengaturan sekolah) untuk lembar jawaban.
        // Tinggi dibatasi 16 mm agar seluruh header muat di atas area jawaban.
        $letterhead = app(LjkSchoolLetterhead::class)->data($exam->school, 22.0);

        // Wacana/soal cerita & dukungan teks Arab untuk lembar jawaban
        // (butir diambil sekali agar tidak berulang query).
        $soalSheet = app(LjkSoalSheet::class);
        $soalQuestions = $soalSheet->questions($exam);
        $wacanaRanges = $soalSheet->rangesFromBlocks($soalSheet->blocks($soalQuestions));
        $examBahasa = $soalSheet->examBahasa($exam);
        $arabicNumbers = $soalSheet->arabicNumbersFrom($soalQuestions);
        $hasArabic = $arabicNumbers !== [];
        $arabAngka = $examBahasa === 'arab' && (string) config('ljk.soal.angka_arab', 'auto') === 'auto';

        // Header lembar jawaban disamakan dengan lembar soal.
        $fontSize = round((float) config('ljk.soal.font_size', 12.0) * LjkPdfFonts::sizeScale(), 2);
        $headerMm = (float) ($template->geometry['header_height_mm'] ?? 0);

        if ($headerMm <= 0) {
            // Template lama: gunakan y soal pertama sebagai batas header.
            $headerMm = (float) (collect($template->geometry['questions'] ?? [])->min('y_mm') ?: 58.0);
        }

        return view('ljk.sheet-pdf', [
            'exam' => $exam,
            'participant' => $participant,
            'student' => $participant->student,
            'sheet' => $sheet,
            'template' => $template,
            'geometry' => $template->geometry,
            'page' => $page,
            'master' => $master,
            'masterNote' => $masterNote,
            'school' => $exam->school,
            'schoolName' => $letterhead['schoolName'],
            'schoolAddress' => $letterhead['address'],
            'kopBase64' => $letterhead['kopBase64'],
            'kopWidthMm' => $letterhead['kopWidthMm'] ?? 145.0,
            'kopHeightMm' => $letterhead['kopHeightMm'],
            'logoBase64' => $letterhead['logoBase64'],
            'arabicStack' => LjkPdfFonts::arabicStack(),
            'arabicFaceCss' => LjkPdfFonts::arabicFaceCss(),
            'wacanaRanges' => $wacanaRanges,
            'arabicNumbers' => $arabicNumbers,
            'examBahasa' => $examBahasa,
            'arabAngka' => $arabAngka,
            'hurufArab' => (array) config('ljk.soal.huruf_arab', ['ا', 'ب', 'ج', 'د', 'هـ']),
            'hasArabic' => $hasArabic,
            'instruksiArab' => (string) config('ljk.soal.instruksi_arab', ''),
            // Header LJK (mengikuti format lembar soal)
            'judulLjk' => $soalSheet->judulLjk($exam),
            'identityRows' => $soalSheet->identityRows($exam, $participant),
            'karakter' => (array) config('ljk.soal.penanaman_karakter', []),
            'instruksiLjk' => (string) config('ljk.soal.instruksi_ljk', ''),
            'fontSize' => $fontSize,
            'fontStack' => LjkPdfFonts::stack(),
            'faceCss' => LjkPdfFonts::faceCss(),
            'headerMm' => $headerMm,
            'qrDataUri' => $master ? null : $this->qrDataUri($exam, $participant, $page),
            'stripBits' => $master ? [] : $this->stripBits($exam, $participant, $page),
        ])->render();
    }
}
