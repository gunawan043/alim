<?php

declare(strict_types=1);

namespace App\Services\Ljk;

use App\Models\School;

/**
 * Kop lembaga untuk PDF LJK (lembar soal & lembar jawaban) — tertaut langsung
 * dengan pengaturan sekolah:
 *
 *  - `schools.kop_path`  → gambar kop surat (dipakai penuh, maks 20 mm)
 *  - `schools.logo_path` → logo (bila kop gambar tidak ada)
 *  - nama, alamat, kota, telepon → teks kop bila gambar belum diunggah
 *
 * Semua berkas dibaca dari disk publik (`storage/app/public/...`) lalu
 * dijadikan data-URI agar aman dirender Dompdf tanpa akses jaringan.
 */
final class LjkSchoolLetterhead
{
    /**
     * @return array{
     *     schoolName: string,
     *     address: string,
     *     kopBase64: ?string,
     *     kopHeightMm: float,
     *     logoBase64: ?string,
     *     hasImage: bool
     * }
     */
    public function data(?School $school): array
    {
        if (! $school) {
            return [
                'schoolName' => 'ALIM',
                'address' => '',
                'kopBase64' => null,
                'kopHeightMm' => 0.0,
                'logoBase64' => null,
                'hasImage' => false,
            ];
        }

        [$kopBase64, $kopHeightMm] = $this->kopData($school);
        $logoBase64 = $this->imageDataUri($school->logo_path);

        return [
            'schoolName' => (string) ($school->name ?? 'ALIM'),
            'address' => $this->address($school),
            'kopBase64' => $kopBase64,
            'kopHeightMm' => $kopHeightMm,
            'logoBase64' => $logoBase64,
            'hasImage' => $kopBase64 !== null || $logoBase64 !== null,
        ];
    }

    /**
     * Alamat satu baris: jalan, kota, telepon.
     */
    public function address(School $school): string
    {
        return trim(implode(' · ', array_filter([
            $school->address,
            $school->city?->name,
            $school->phone ? 'Telp. '.$school->phone : null,
        ])));
    }

    /**
     * Kop surat sekolah (bila diunggah) + tinggi render dalam mm.
     *
     * @return array{0: ?string, 1: float}
     */
    private function kopData(School $school): array
    {
        if (! $school->kop_path) {
            return [null, 0.0];
        }

        $absolute = storage_path('app/public/'.$school->kop_path);
        if (! is_file($absolute)) {
            return [null, 0.0];
        }

        $widthMm = 145.0;
        $heightMm = 0.0;
        $info = @getimagesize($absolute);
        if ($info && (int) $info[0] > 0) {
            $heightMm = round($widthMm * ((int) $info[1] / (int) $info[0]), 2);
        }

        $maxHeight = (float) config('ljk.template.max_kop_height_mm', 20.0);
        $heightMm = $heightMm > 0 ? min($heightMm, $maxHeight) : 18.0;

        return [$this->imageDataUri($school->kop_path), $heightMm];
    }

    /**
     * Berkas gambar (path relatif disk publik) → data-URI, atau null.
     */
    private function imageDataUri(?string $relativePath): ?string
    {
        if (! $relativePath) {
            return null;
        }

        $absolute = storage_path('app/public/'.ltrim($relativePath, '/'));
        if (! is_file($absolute)) {
            return null;
        }

        $ext = strtolower(pathinfo($absolute, PATHINFO_EXTENSION) ?: 'png');
        $mime = in_array($ext, ['jpg', 'jpeg'], true) ? 'image/jpeg' : 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($absolute));
    }
}
