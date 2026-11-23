<?php

declare(strict_types=1);

namespace App\Services\Evaluasi;

use App\Models\Soal;
use Illuminate\Http\Request;

/**
 * Pengaturan cetak naskah soal per guru (kertas, margin, spasi, font).
 *
 * Disimpan di session agar tidak perlu tabel baru dan tetap melekat pada
 * guru yang sedang login. Setiap cetak berikutnya memakai pengaturan terakhir.
 */
class PrintSettingsService
{
    private const SESSION_KEY = 'soal_print_settings';

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
            'paper' => 'A4',
            'orientation' => 'portrait',
            'margin_top' => 20,
            'margin_right' => 20,
            'margin_bottom' => 20,
            'margin_left' => 25,
            'font' => 'times',
            'font_size' => 11,
            'line_height' => 1.45,
            'option_columns' => 1,
            'start_number' => 1,
            'show_key' => true,
            'show_kop' => true,
            'judul' => '',
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
        $settings = $request->hasAny(array_keys($this->defaults()))
            ? array_merge($this->get(), $this->fromRequest($request))
            : $this->get();

        $settings = $this->sanitize($settings);

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
        $fields = array_keys($this->defaults());
        $values = [];

        foreach ($fields as $field) {
            if (! $request->exists($field)) {
                continue;
            }

            $values[$field] = $request->input($field);
        }

        // Checkbox tidak dikirim saat tidak dicentang.
        foreach (['show_key', 'show_kop'] as $checkbox) {
            $values[$checkbox] = (bool) $request->boolean($checkbox);
        }

        return $values;
    }

    /**
     * Batasi nilai ke rentang yang wajar agar naskah tidak rusak.
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function sanitize(array $settings): array
    {
        $clamp = static fn ($value, float $min, float $max, float $default) => is_numeric($value)
            ? max($min, min($max, (float) $value))
            : $default;

        $settings['paper'] = array_key_exists((string) $settings['paper'], self::PAPERS) ? $settings['paper'] : 'A4';
        $settings['orientation'] = $settings['orientation'] === 'landscape' ? 'landscape' : 'portrait';
        $settings['font'] = array_key_exists((string) $settings['font'], self::FONTS) ? $settings['font'] : 'times';

        foreach (['margin_top', 'margin_right', 'margin_bottom', 'margin_left'] as $side) {
            $settings[$side] = (float) $clamp($settings[$side], 5, 50, 20);
        }

        $settings['font_size'] = (int) $clamp($settings['font_size'], 8, 16, 11);
        $settings['line_height'] = round((float) $clamp($settings['line_height'], 1.0, 2.5, 1.45), 2);
        $settings['option_columns'] = in_array((int) $settings['option_columns'], [1, 2], true)
            ? (int) $settings['option_columns']
            : 1;
        $settings['start_number'] = (int) $clamp($settings['start_number'], 1, 999, 1);
        $settings['show_key'] = (bool) $settings['show_key'];
        $settings['show_kop'] = (bool) $settings['show_kop'];
        $settings['judul'] = mb_substr(trim((string) $settings['judul']), 0, 150);

        return $settings;
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
}
