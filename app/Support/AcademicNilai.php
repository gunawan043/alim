<?php

namespace App\Support;

use App\Models\NilaiSumatif;
use App\Models\TeacherAdminBook;

/**
 * Aturan nilai akademik yang dipakai bersama oleh Buku Administrasi,
 * Leger, Rapor, dan export — agar tidak ada ambang/KKM yang berbeda-beda.
 */
class AcademicNilai
{
    public const DEFAULT_KKM = 75.0;

    /** Ambang predikat (tertinggi lebih dulu). */
    public const PREDIKAT = [
        ['min' => 95.0, 'label' => "Mumtaz Murtafi'"],
        ['min' => 90.0, 'label' => 'Mumtaz'],
        ['min' => 85.0, 'label' => 'Jayyid Jiddan'],
        ['min' => 80.0, 'label' => 'Jayyid'],
        ['min' => 75.0, 'label' => 'Maqbul'],
    ];

    public const PREDIKAT_TERENDAH = 'Roosib';

    /**
     * Predikat dari nilai rata-rata. Null → '—'.
     */
    public static function predikat(?float $rata): string
    {
        if ($rata === null) {
            return '—';
        }

        foreach (self::PREDIKAT as $p) {
            if ($rata >= $p['min']) {
                return $p['label'];
            }
        }

        return self::PREDIKAT_TERENDAH;
    }

    /**
     * Nilai KKM efektif satu mapel (buku admin). Default 75 bila KKTP belum diisi.
     */
    public static function kkm(?TeacherAdminBook $book): float
    {
        $kkm = self::rawKkm($book);

        return $kkm !== null ? (float) $kkm : self::DEFAULT_KKM;
    }

    /**
     * Label KKM untuk dokumen cetak — '—' bila belum diatur (Leger),
     * angka bila sudah diatur.
     */
    public static function kkmLabel(?TeacherAdminBook $book): string
    {
        $kkm = self::rawKkm($book);

        if ($kkm === null) {
            return '—';
        }

        return rtrim(rtrim(number_format((float) $kkm, 2, '.', ''), '0'), '.');
    }

    /**
     * Baca kkm_score mentah tanpa memicu relasi saat FK kosong.
     */
    private static function rawKkm(?TeacherAdminBook $book): mixed
    {
        if (! $book || empty($book->kktp_id)) {
            return null;
        }

        return $book->kktp?->kkm_score;
    }

    /**
     * Keterangan capaian: Terlampaui / Tercapai / Belum Tuntas.
     * Konsisten antara Rapor, Leger, dan export.
     */
    /**
     * Nama mapel untuk kolom sempit — otomatis:
     * nama pendek dibiarkan utuh; nama panjang diringkas per kata umum, lalu dipotong rapi.
     */
    public static function shortSubjectName(?string $name, int $max = 13): string
    {
        $name = trim(preg_replace('/\s+/', ' ', (string) $name));
        if ($name === '') {
            return '';
        }

        if (mb_strlen($name) <= $max) {
            return $name;
        }

        $map = [
            'bahasa' => 'B.',
            'pendidikan' => 'Pend.',
            'hafalan' => 'Haf.',
            'sejarah' => 'Sej.',
            'prakarya' => 'Prak.',
            'kepercayaan' => 'Keperc.',
            'kewarganegaraan' => 'Kwn.',
        ];

        $words = explode(' ', $name);
        $out = [];
        foreach ($words as $w) {
            $lw = mb_strtolower($w);
            $out[] = $map[$lw] ?? $w;
        }

        $short = implode(' ', $out);
        if (mb_strlen($short) <= $max + 2) {
            return $short;
        }

        $result = '';
        foreach ($out as $w) {
            $candidate = $result === '' ? $w : $result.' '.$w;
            if (mb_strlen($candidate) > $max) {
                break;
            }
            $result = $candidate;
        }

        if ($result === '') {
            $result = mb_substr($short, 0, $max);
        }

        return $result === $short ? $short : $result.'…';
    }

    public static function keterangan(?float $nilai, ?TeacherAdminBook $book): string
    {
        if ($nilai === null) {
            return '';
        }

        $kkm = self::kkm($book);

        if ($nilai > $kkm) {
            return 'Terlampaui';
        }

        if ($nilai >= $kkm) {
            return 'Tercapai';
        }

        return 'Belum Tuntas';
    }

    /**
     * Apakah nilai di bawah KKM (untuk pewarnaan di dokumen cetak).
     */
    public static function belowKkm(?float $nilai, ?TeacherAdminBook $book): bool
    {
        if ($nilai === null) {
            return false;
        }

        return $nilai < self::kkm($book);
    }

    /**
     * Nilai tampil menurut jenis dokumen: STS → sts; SAS → NR Final (fallback SAS).
     * Satu aturan untuk Leger, Rapor, export, dan arsip dokumen.
     */
    public static function resolveTampil(string $jenis, ?NilaiSumatif $row): ?float
    {
        if (! $row) {
            return null;
        }

        if ($jenis === 'sas') {
            if ($row->nr_final !== null) {
                return (float) $row->nr_final;
            }

            return $row->sas !== null ? (float) $row->sas : null;
        }

        return $row->sts !== null ? (float) $row->sts : null;
    }

    /**
     * Kelompok mapel: 1–3 = Agama/B.Arab/Hadits (grup AGAMA), 4 = Umum.
     */
    public static function subjectGroup(string $name): int
    {
        $nameLower = strtolower($name);

        if (preg_match('/(aqidah|adab|fiqih|tahfidz|hafalan hadits)/i', $nameLower)) {
            return 1;
        }
        if (preg_match('/(bahasa arab|b\.? ?arab|qowaid|ta[\'\"]?bir|sharaf)/i', $nameLower)) {
            return 2;
        }
        if (preg_match('/(hadits|hadist)/i', $nameLower)) {
            return 3;
        }

        return 4;
    }

    /**
     * Urutan mapel: kelompok Agama → Arab → Hadits → Umum, lalu alfabetis.
     *
     * @return array{0: int, 1: string}
     */
    public static function subjectSortOrder(string $name, string $category): array
    {
        return [self::subjectGroup($name), strtolower($name)];
    }
}
