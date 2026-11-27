<?php

namespace App\Services;

use App\Models\TahfidzGradebook;
use App\Models\TahfidzGradebookScore;

/**
 * Aturan hitung NR Final format tahfidz.
 *
 * NR Final = (Ziyadah×wZ + Rabt×wR + STS×wS + SAS×wA) / total bobot
 *
 * Berbeda dari mapel umum (NilaiSumatif::calcNrFinal) yang mengembalikan
 * null bila ada komponen kosong: pada tahfidz pembobotan dinormalisasi
 * terhadap komponen yang sudah terisi. Alasannya, Ziyadah dan Rabt diisi
 * berkelanjutan sepanjang semester — menuntut keempat kolom terisi
 * sekaligus akan membuat nilai tidak pernah muncul sampai akhir periode.
 */
class TahfidzGradebookCalculator
{
    /** Kolom nilai format tahfidz, dalam urutan tampil. */
    public const KEYS = ['npt', 'ziyadah', 'rabt', 'tikrar', 'sts', 'sas'];

    /**
     * Terapkan nr_final ke baris nilai (tanpa menyimpan).
     */
    public function apply(TahfidzGradebookScore $score, TahfidzGradebook $book): void
    {
        $score->nr_final = $this->calculate(
            [
                'npt' => $score->npt,
                'ziyadah' => $score->ziyadah,
                'rabt' => $score->rabt,
                'tikrar' => $score->tikrar,
                'sts' => $score->sts,
                'sas' => $score->sas,
            ],
            $book->weights(),
        );
    }

    /**
     * @param  array<string, float|int|null>  $values
     * @param  array<string, float>  $weights
     */
    public function calculate(array $values, array $weights): ?float
    {
        $sum = 0.0;
        $weightSum = 0.0;

        foreach ($weights as $key => $weight) {
            $value = $values[$key] ?? null;

            if ($value === null || $value === '' || ! is_numeric($value)) {
                continue;
            }

            if ($weight <= 0) {
                continue;
            }

            $sum += (float) $value * $weight;
            $weightSum += $weight;
        }

        if ($weightSum <= 0) {
            return null;
        }

        return round($sum / $weightSum, 2);
    }

    /**
     * Validasi bobot: tiap komponen 0–100 dan totalnya 100.
     *
     * @param  array<string, mixed>  $weights
     * @return array<string, string> pesan kesalahan per kolom (kosong = valid)
     */
    public function validateWeights(array $weights): array
    {
        $errors = [];
        $total = 0.0;

        foreach (self::KEYS as $key) {
            $value = $weights[$key] ?? null;

            if (! is_numeric($value) || (float) $value < 0 || (float) $value > 100) {
                $errors[$key] = 'Bobot harus bernilai 0–100.';
                continue;
            }

            $total += (float) $value;
        }

        if ($errors === [] && abs($total - 100) > 0.01) {
            $errors['total'] = 'Total bobot harus 100% (sekarang '.rtrim(rtrim(number_format($total, 1, '.', ''), '0'), '.').'%).';
        }

        return $errors;
    }
}
