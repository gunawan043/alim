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
        $payload = $this->buildQrPayload($exam, $participant, $participant->sheet ?? LjkSheet::query()->where('participant_id', $participant->id)->firstOrFail(), $page);

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

        $school = $exam->school;
        [$kopBase64, $kopHeightMm] = $this->kopData($school);

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
            'school' => $school,
            'kopBase64' => $kopBase64,
            'kopHeightMm' => $kopHeightMm,
            'logoBase64' => $this->logoDataUri($school),
            'qrDataUri' => $master ? null : $this->qrDataUri($exam, $participant, $page),
            'stripBits' => $master ? [] : $this->stripBits($exam, $participant, $page),
        ])->render();
    }

    /**
     * Kop surat sekolah (bila diunggah) + tinggi render dalam mm.
     *
     * @return array{0: ?string, 1: float}
     */
    private function kopData(?\App\Models\School $school): array
    {
        if (! $school || ! $school->kop_path) {
            return [null, 0.0];
        }

        $absolute = storage_path('app/public/'.$school->kop_path);
        if (! file_exists($absolute)) {
            return [null, 0.0];
        }

        $ext = strtolower(pathinfo($absolute, PATHINFO_EXTENSION) ?: 'png');
        $mime = in_array($ext, ['jpg', 'jpeg'], true) ? 'image/jpeg' : 'image/png';

        $widthMm = 145.0;
        $heightMm = 0.0;
        $info = @getimagesize($absolute);
        if ($info && (int) $info[0] > 0) {
            $heightMm = round($widthMm * ((int) $info[1] / (int) $info[0]), 2);
        }

        $maxHeight = (float) config('ljk.template.max_kop_height_mm', 24.0);
        $heightMm = $heightMm > 0 ? min($heightMm, $maxHeight) : 18.0;

        return ['data:'.$mime.';base64,'.base64_encode((string) file_get_contents($absolute)), $heightMm];
    }

    /**
     * Logo sekolah (bila ada) sebagai data-URI.
     */
    private function logoDataUri(?\App\Models\School $school): ?string
    {
        if (! $school || ! $school->logo_path) {
            return null;
        }

        $absolute = storage_path('app/public/'.$school->logo_path);
        if (! file_exists($absolute)) {
            return null;
        }

        $ext = strtolower(pathinfo($absolute, PATHINFO_EXTENSION) ?: 'png');
        $mime = in_array($ext, ['jpg', 'jpeg'], true) ? 'image/jpeg' : 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($absolute));
    }
}
