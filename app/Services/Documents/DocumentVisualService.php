<?php

namespace App\Services\Documents;

use App\Models\School;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Preferensi tampilan & mode validasi dokumen per jenis.
 *
 * Penting: mode validasi (QR/PAdES) dan tampilan visual (TTD/stempel) adalah
 * dua hal terpisah.
 * - Memilih QR tidak menghilangkan stempel/TTD.
 * - Memilih PAdES juga tidak menghilangkan stempel/TTD.
 * - Bila TTD/stempel disembunyikan, dokumen tetap sah lewat registry/PAdES,
 *   hanya tampilan visualnya kosong.
 *
 * Rapor tidak memakai pengaturan ini karena punya tata letak sendiri
 * (rapor_distribution_settings).
 */
class DocumentVisualService
{
    /** Jenis dokumen yang memakai pengaturan ini. */
    public const TYPES = ['mutasi', 'sk', 'skl'];

    public const LABELS = [
        'mutasi' => 'Surat Mutasi',
        'sk' => 'Surat Keputusan (SK)',
        'skl' => 'Surat Keterangan Lulus (SKL)',
    ];

    public const MODES = [
        'visual' => 'TTD & stempel saja (tanpa QR)',
        'qr' => 'QR registry saja (tanpa TTD/stempel)',
        'pades' => 'PAdES + QR (tanpa TTD/stempel)',
    ];

    /** Default ukuran (mm). */
    public const DEFAULTS = [
        'show_ttd_ksp' => true,
        'show_stamp' => true,
        'ttd_width' => 30.0,
        'ttd_height' => 15.0,
        'ttd_margin' => 2.0,
        'stamp_width' => 30.0,
        'stamp_height' => 30.0,
        'stamp_margin' => 2.0,
    ];

    /**
     * Pengaturan efektif untuk satu jenis dokumen.
     *
     * @return array{show_ttd_ksp: bool, show_stamp: bool, validation_mode: string}
     */
    public function settings(?School $school, string $type): array
    {
        $all = is_array($school?->document_visual_settings) ? $school->document_visual_settings : [];
        $stored = is_array($all[$type] ?? null) ? $all[$type] : [];

        return [
            'show_ttd_ksp' => (bool) ($stored['show_ttd_ksp'] ?? self::DEFAULTS['show_ttd_ksp']),
            'show_stamp' => (bool) ($stored['show_stamp'] ?? self::DEFAULTS['show_stamp']),
            'validation_mode' => $this->normalizeMode($stored['validation_mode'] ?? null, $type),
            'ttd_width' => $this->number($stored['ttd_width'] ?? null, self::DEFAULTS['ttd_width'], 5, 100),
            'ttd_height' => $this->number($stored['ttd_height'] ?? null, self::DEFAULTS['ttd_height'], 5, 60),
            'ttd_margin' => $this->number($stored['ttd_margin'] ?? null, self::DEFAULTS['ttd_margin'], 0, 30),
            'stamp_width' => $this->number($stored['stamp_width'] ?? null, self::DEFAULTS['stamp_width'], 5, 100),
            'stamp_height' => $this->number($stored['stamp_height'] ?? null, self::DEFAULTS['stamp_height'], 5, 100),
            'stamp_margin' => $this->number($stored['stamp_margin'] ?? null, self::DEFAULTS['stamp_margin'], 0, 30),
        ];
    }

    /**
     * Mode validasi tersimpan/normal (tidak melempar exception).
     */
    public function validationMode(?School $school, string $type): string
    {
        return $this->settings($school, $type)['validation_mode'];
    }

    /**
     * Mode untuk mencetak. Mode PAdES ditolak selama provider PSrE belum siap,
     * agar tidak terbit dokumen yang tampak sah tanpa signature.
     *
     * @throws RuntimeException
     */
    public function modeForPrinting(?School $school, string $type): string
    {
        $mode = $this->validationMode($school, $type);

        if ($mode === 'pades' && ! app(PadesSigningService::class)->available()) {
            throw new RuntimeException(
                'Mode PAdES belum dapat dipakai karena provider PSrE dan sertifikat personal belum tersedia. '
                .'Ubah mode dokumen ke QR atau visual terlebih dahulu.'
            );
        }

        return $mode;
    }

    /**
     * Data siap pakai untuk Blade/Dompdf.
     *
     * @return array{show_ttd_ksp: bool, show_stamp: bool, validation_mode: string, ttd_ksp: ?string, stamp: ?string}
     */
    public function viewData(?School $school, string $type): array
    {
        $settings = $this->settings($school, $type);

        // Mode QR/PAdES adalah pengesahan dokumen. TTD/stempel tidak dicetak
        // agar tidak ada dua klaim pengesahan yang bersaing.
        $hideVisual = in_array($settings['validation_mode'], ['qr', 'pades'], true);

        return array_merge($settings, [
            'show_ttd_ksp' => $settings['show_ttd_ksp'] && ! $hideVisual,
            'show_stamp' => $settings['show_stamp'] && ! $hideVisual,
            'ttd_ksp' => $this->imageDataUri($school?->ttd_ksp_path),
            'stamp' => $this->imageDataUri($school?->stamp_path),
        ]);
    }

    /**
     * Simpan pilihan dari form media sekolah.
     *
     * @param  array<string, array<string, mixed>>  $input
     *
     * @throws ValidationException
     */
    public function update(School $school, array $input): void
    {
        foreach (self::TYPES as $type) {
            $values = is_array($input[$type] ?? null) ? $input[$type] : [];
            $this->updateType($school, $type, $values);
        }
    }

    /**
     * Simpan pengaturan satu jenis dokumen saja.
     *
     * @param  array<string, mixed>  $values
     *
     * @throws ValidationException
     */
    public function updateType(School $school, string $type, array $values): void
    {
        if (! in_array($type, self::TYPES, true)) {
            throw ValidationException::withMessages([
                'type' => 'Jenis dokumen tidak dikenal.',
            ]);
        }

        $mode = $this->normalizeMode($values['validation_mode'] ?? null, $type);

        if ($mode === 'pades' && ! app(PadesSigningService::class)->available()) {
            throw ValidationException::withMessages([
                'document_visual' => 'Mode PAdES belum dapat dipakai: provider PSrE dan sertifikat personal belum tersedia. '
                    .'Pilih mode QR atau visual.',
            ]);
        }

        $current = is_array($school->document_visual_settings) ? $school->document_visual_settings : [];
        $current[$type] = [
            'show_ttd_ksp' => filter_var($values['show_ttd_ksp'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'show_stamp' => filter_var($values['show_stamp'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'validation_mode' => $mode,
            'ttd_width' => $this->number($values['ttd_width'] ?? null, self::DEFAULTS['ttd_width'], 5, 100),
            'ttd_height' => $this->number($values['ttd_height'] ?? null, self::DEFAULTS['ttd_height'], 5, 60),
            'ttd_margin' => $this->number($values['ttd_margin'] ?? null, self::DEFAULTS['ttd_margin'], 0, 30),
            'stamp_width' => $this->number($values['stamp_width'] ?? null, self::DEFAULTS['stamp_width'], 5, 100),
            'stamp_height' => $this->number($values['stamp_height'] ?? null, self::DEFAULTS['stamp_height'], 5, 100),
            'stamp_margin' => $this->number($values['stamp_margin'] ?? null, self::DEFAULTS['stamp_margin'], 0, 30),
        ];

        $school->document_visual_settings = $current;
    }

    /**
     * Mode yang boleh dipakai saat ini (untuk pilihan di UI).
     *
     * @return array<int, array{value: string, label: string, disabled: bool}>
     */
    public function modeOptions(): array
    {
        $padesReady = app(PadesSigningService::class)->available();

        return collect(self::MODES)
            ->map(fn (string $label, string $value) => [
                'value' => $value,
                'label' => $value === 'pades' && ! $padesReady ? $label.' — belum tersedia' : $label,
                'disabled' => $value === 'pades' && ! $padesReady,
            ])
            ->values()
            ->all();
    }

    /**
     * Ubah file gambar privat/public menjadi data URI agar aman di Dompdf.
     */
    public function imageDataUri(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        $mime = $disk->mimeType($path) ?: 'image/png';

        if (! str_starts_with($mime, 'image/')) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode((string) $disk->get($path));
    }

    private function number($value, float $default, float $min, float $max): float
    {
        if (! is_numeric($value)) {
            return $default;
        }

        return round(min($max, max($min, (float) $value)), 1);
    }

    private function normalizeMode(?string $mode, string $type): string
    {
        $mode = $mode ?: (string) config("documents.modes.{$type}", config('documents.default_mode', 'qr'));

        return array_key_exists($mode, self::MODES) ? $mode : 'qr';
    }
}
