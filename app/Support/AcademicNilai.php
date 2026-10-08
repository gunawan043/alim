<?php

namespace App\Support;

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
}
