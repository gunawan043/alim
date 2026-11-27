<?php

namespace App\Services;

/**
 * Urutan jabatan pada matriks Surat Keputusan.
 *
 * Kolom `urutan` pada master jabatan diisi tidak konsisten (banyak jabatan
 * berbeda memakai angka yang sama, dan "Guru Kelas" bernilai lebih kecil dari
 * "Kepala Tata Usaha"), sehingga tidak bisa dipakai langsung sebagai hierarki.
 * Kelas ini menurunkan peringkat dari NAMA jabatan agar urutan selalu mengikuti
 * hierarki satuan pendidikan:
 *
 *   Kepala Satuan Pendidikan
 *     -> Wakil Kepala
 *       -> Kepala unit/bidang
 *         -> Koordinator
 *           -> Guru (Umum, Agama, Tahfidz, Bahasa, Hadits, Kelas)
 *             -> Tenaga Kependidikan / Staf
 *               -> Tata Usaha
 *
 * Peringkat kecil tampil lebih dulu. Dipakai bersama oleh form Tambah SK,
 * form Edit SK, dan penyusun matriks agar tidak ada dua aturan yang berbeda.
 */
class DecreePositionOrdering
{
    /** Tingkat jabatan, dibaca dari nama. */
    public const RANK_KEPALA_SEKOLAH = 1;

    public const RANK_WAKIL = 2;

    public const RANK_KEPALA_UNIT = 3;

    public const RANK_KOORDINATOR = 4;

    public const RANK_GURU = 5;

    public const RANK_STAF = 6;

    public const RANK_TATA_USAHA = 7;

    public const RANK_LAINNYA = 8;

    /** Dipakai bila jabatan berada pada dua tingkat sekaligus (mis. "Wakil Kepala Tata Usaha"). */
    private const SUB_PRIORITY = [
        self::RANK_KEPALA_SEKOLAH => 0,
        self::RANK_WAKIL => 0,
        self::RANK_KEPALA_UNIT => 0,
        self::RANK_KOORDINATOR => 0,
        self::RANK_GURU => 0,
        self::RANK_STAF => 0,
        self::RANK_TATA_USAHA => 0,
        self::RANK_LAINNYA => 0,
    ];

    /**
     * Peringkat satu nama jabatan (1 = paling tinggi).
     */
    public function rank(?string $position): int
    {
        $name = $this->normalize($position);

        if ($name === '') {
            return self::RANK_LAINNYA;
        }

        // "Wakil Kepala Tata Usaha" tetap wakil, bukan TU, agar urutannya wajar.
        $isKepala = $this->startsWith($name, 'kepala') || $this->startsWith($name, 'ketua');
        $isWakil = $this->startsWith($name, 'wakil')
            || $this->startsWith($name, 'wadir')
            || $this->startsWith($name, 'wakasek')
            || $this->startsWith($name, 'wakil kepala');

        if ($isWakil) {
            return self::RANK_WAKIL;
        }

        // Kepala Satuan Pendidikan / Direktur / Mudir menempati puncak.
        if ($isKepala && ! $this->isTataUsaha($name)
            && ($this->contains($name, 'satuan pendidikan')
                || $this->contains($name, 'sekolah')
                || $this->contains($name, 'madrasah')
                || $this->startsWith($name, 'mudir')
                || $this->startsWith($name, 'direktur')
                || $this->startsWith($name, 'pengasuh'))) {
            return self::RANK_KEPALA_SEKOLAH;
        }

        if ($this->startsWith($name, 'mudir') || $this->startsWith($name, 'direktur')) {
            return self::RANK_KEPALA_SEKOLAH;
        }

        // Tata Usaha naik ke tingkat sendiri, kecuali ia jabatan kepala/koordinator.
        if ($this->isTataUsaha($name) && ! $isKepala && ! $this->startsWith($name, 'koordinator')) {
            return self::RANK_TATA_USAHA;
        }

        if ($isKepala) {
            return self::RANK_KEPALA_UNIT;
        }

        if ($this->startsWith($name, 'koordinator')) {
            return self::RANK_KOORDINATOR;
        }

        if ($this->startsWith($name, 'guru') || $this->contains($name, ' guru')) {
            return self::RANK_GURU;
        }

        if ($this->startsWith($name, 'staf') || $this->startsWith($name, 'staff')
            || $this->startsWith($name, 'tenaga') || $this->isTataUsaha($name)) {
            return self::RANK_STAF;
        }

        return self::RANK_LAINNYA;
    }

    /**
     * Kunci pengurutan lengkap: peringkat jabatan, lalu kelompok jabatan,
     * lalu nama. Mengembalikan string agar bisa dipakai sortBy() langsung.
     */
    public function sortKey(?string $position, string $name): string
    {
        $rank = $this->rank($position);

        return sprintf(
            '%02d|%02d|%s',
            $rank,
            self::SUB_PRIORITY[$rank] ?? 0,
            mb_strtolower(trim($name))
        );
    }

    /**
     * Kunci pengurutan berdasarkan peringkat master jabatan yang dipilih,
     * dengan hierarki nama sebagai penentu bila peringkat master sama.
     *
     * @param  array<string, int>  $masterOrder  nama jabatan (lowercase) => urutan
     */
    public function sortKeyWithMaster(?string $position, string $name, array $masterOrder = []): string
    {
        $rank = $this->rank($position);
        $master = $masterOrder[mb_strtolower(trim((string) $position))] ?? 999;

        return sprintf(
            '%02d|%03d|%s',
            $rank,
            $master,
            mb_strtolower(trim($name))
        );
    }

    private function normalize(?string $position): string
    {
        $name = mb_strtolower(trim((string) $position));

        return preg_replace('/\s+/u', ' ', str_replace(['-', '_'], ' ', $name)) ?? '';
    }

    private function startsWith(string $name, string $prefix): bool
    {
        return str_starts_with($name, $prefix);
    }

    private function contains(string $name, string $needle): bool
    {
        return str_contains($name, $needle);
    }

    private function isTataUsaha(string $name): bool
    {
        return $this->contains($name, 'tata usaha')
            || preg_match('/\btu\b/', $name) === 1;
    }
}
