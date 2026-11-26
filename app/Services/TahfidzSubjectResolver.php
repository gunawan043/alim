<?php

namespace App\Services;

use App\Models\Subject;

/**
 * Satu-satunya penentu apakah sebuah mapel memakai format penilaian tahfidz.
 *
 * Sebelumnya aturan ini hidup di dua tempat dengan hasil berbeda
 * (TahfidzRaporService: nama/kode memuat "hafalan qur" dianggap tahfidz,
 *  JadwalGeneratorService: hanya rumpun/nama "tahfidz" + kode THF), sehingga
 * mapel bernama "Hafalan Quran" dinilai sebagai tahfidz pada rapor tetapi
 * tidak pada penjadwalan.
 *
 * Kini sumber kebenaran adalah kolom `subjects.penilaian_format`. Pencocokan
 * nama hanya dipakai untuk MENYARANKAN mapel yang belum ditandai — tidak
 * pernah menentukan nilai. Dengan begitu typo tidak bisa menggeser format
 * penilaian secara diam-diam.
 */
class TahfidzSubjectResolver
{
    public const FORMAT_UMUM = 'umum';

    public const FORMAT_TAHFIDZ = 'tahfidz';

    /**
     * Apakah mapel memakai format penilaian tahfidz (Ziyadah, Rabt, STS, SAS)?
     * Hanya kolom penanda yang dibaca.
     */
    public function isTahfidz(?Subject $subject): bool
    {        if (! $subject) {
            return false;
        }

        // Model dari query tanpa kolom penanda (mis. select terbatas) → jangan
        // menebak; perlakukan sebagai umum.
        if (! array_key_exists('penilaian_format', $subject->getAttributes())) {
            return false;
        }

        return $this->normalizeFormat($subject->penilaian_format) === self::FORMAT_TAHFIDZ;
    }

    /**
     * Nama lama yang masih dipakai pemanggil lain (rapor, gradebook, penilaian
     * tahfidz). Diteruskan ke isTahfidz() agar tetap satu aturan.
     */
    public function isTahfidzSubject(?Subject $subject): bool
    {
        return $this->isTahfidz($subject);
    }

    public function isTahfidzId(?string $subjectId): bool
    {
        if (! $subjectId) {
            return false;
        }

        return $this->isTahfidz(Subject::find($subjectId));
    }

    /**
     * Tebakan nama untuk ditampilkan sebagai SARAN di halaman verifikasi.
     * Jangan dipakai untuk menghitung nilai.
     *
     * @return array{exact: bool, score: float|null}
     *         exact  : nama/kode/rumpun jelas menyebut tahfidz
     *         score  : 0..1 kemiripan dengan "tahfidz" (untuk menangkap typo
     *                  seperti "Thafidzul"), null bila tidak relevan
     */
    public function suggestByName(?Subject $subject, float $threshold = 0.60): array
    {
        if (! $subject) {
            return ['exact' => false, 'score' => null];
        }

        $name = $this->normalizeText((string) ($subject->name ?? ''));
        $code = $this->normalizeText((string) ($subject->code ?? ''));
        $group = $this->normalizeText((string) ($subject->subjectGroup?->name ?? ''));

        $exact = str_contains($group, 'tahfidz')
            || str_contains($group, 'tahfiz')
            || str_contains($name, 'tahfidz')
            || str_contains($name, 'tahfiz')
            || preg_match('/^thf\d*$/', $code) === 1;

        if ($exact) {
            return ['exact' => true, 'score' => 1.0];
        }

        $best = null;
        foreach ($this->words($name) as $word) {
            $similarity = $this->similarity($word, 'tahfidz');
            if ($best === null || $similarity > $best) {
                $best = $similarity;
            }
        }

        return [
            'exact' => false,
            'score' => ($best !== null && $best >= $threshold) ? round($best, 3) : null,
        ];
    }

    /**
     * Normalisasi ejaan untuk pembandingan saran saja: huruf kecil, buang
     * tanda baca, samakan transliterasi yang tertukar (dz/zh/z, q/k, f/ph/v).
     *
     * "th" dan "dh" sengaja TIDAK diganti: pada "thafidzul" penggantian itu
     * justru menghapus huruf h yang membedakan, sehingga typo jadi makin jauh.
     */
    public function normalizeText(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return strtr($value, [
            'dz' => 'z',
            'zh' => 'z',
            'ph' => 'f',
            'kh' => 'h',
            'q' => 'k',
            'v' => 'f',
        ]);
    }

    private function normalizeFormat(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));

        return $value === self::FORMAT_TAHFIDZ ? self::FORMAT_TAHFIDZ : self::FORMAT_UMUM;
    }

    /** @return array<int, string> */
    private function words(string $normalized): array
    {
        return array_values(array_filter(
            explode(' ', $normalized),
            fn ($word) => mb_strlen($word) >= 4,
        ));
    }

    private function similarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }

        $lenA = mb_strlen($a);
        $lenB = mb_strlen($b);
        $max = max($lenA, $lenB, 6);
        if ($lenA === 0 || $lenB === 0) {
            return 0.0;
        }

        // Bandingkan kata sepanjang kata pembanding, dengan biaya transposisi
        // murah. Inilah yang menangkap typo seperti "thafidzul" → "thafizul"
        // (2 huruf tertukar) tanpa membuat kata lain seperti "Tahlil" lolos.
        $trimmed = mb_substr($a, 0, min($lenA, $lenB));

        return max(
            1 - ($this->distance($trimmed, $b) / $max),
            1 - ($this->distance($a, $b) / $max),
        );
    }

    /**
     * Jarak edit ala Damerau–Levenshtein (transposisi huruf berbiaya 1),
     * agar salah ketik akrab seperti "dzid"/"dizd" tidak dihitung dua kesalahan.
     */
    private function distance(string $a, string $b): int
    {
        $la = mb_strlen($a);
        $lb = mb_strlen($b);
        $prev2 = [];
        $prev = range(0, $lb);

        for ($i = 1; $i <= $la; $i++) {
            $cur = [$i];
            $charA = mb_substr($a, $i - 1, 1);
            for ($j = 1; $j <= $lb; $j++) {
                $cost = $charA === mb_substr($b, $j - 1, 1) ? 0 : 1;
                $value = min($prev[$j] + 1, $cur[$j - 1] + 1, $prev[$j - 1] + $cost);

                if ($i > 1 && $j > 1
                    && $charA === mb_substr($b, $j - 2, 1)
                    && mb_substr($a, $i - 2, 1) === mb_substr($b, $j - 1, 1)) {
                    $value = min($value, ($prev2[$j - 2] ?? 0) + 1);
                }

                $cur[$j] = $value;
            }
            $prev2 = $prev;
            $prev = $cur;
        }

        return $prev[$lb];
    }
}
