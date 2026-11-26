<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Satu sumber kebenaran untuk urutan daftar santri.
 *
 * Bawaan sekolah disimpan di system_settings, sedangkan setiap halaman daftar
 * boleh membatalkannya sesaat lewat query `?sort=`. Karena semua modul memakai
 * scope ini, urutan santri tidak akan berbeda-beda antar halaman.
 */
class StudentOrder
{
    public const NAME = 'name';

    public const NIS = 'nis';

    /** Urutan yang boleh dipakai; nilai lain diabaikan dan kembali ke bawaan. */
    public const ALLOWED = [self::NAME, self::NIS];

    /** Parameter query untuk membatalkan bawaan sekolah di satu halaman. */
    public const QUERY_KEY = 'sort';

    // ── Pengaturan bawaan per sekolah ───────────────────────────────

    public static function settingKey(?string $schoolId): string
    {
        return 'student_sort_order:'.($schoolId ?: 'default');
    }

    public static function defaultFor(?string $schoolId): string
    {
        $value = SystemSetting::get(self::settingKey($schoolId));

        return self::isAllowed($value) ? $value : self::NAME;
    }

    public static function saveDefault(?string $schoolId, string $order): void
    {
        if (! self::isAllowed($order) || ! $schoolId) {
            return;
        }

        SystemSetting::set(self::settingKey($schoolId), $order, 'students');
    }

    // ── Penyelesaian urutan yang berlaku di sebuah request ──────────

    /**
     * Urutan efektif: `?sort=` pada request → bawaan sekolah → nama.
     */
    public static function resolve(?Request $request, ?string $schoolId): string
    {
        $fromQuery = $request?->query(self::QUERY_KEY);

        if (self::isAllowed($fromQuery)) {
            return $fromQuery;
        }

        return self::defaultFor($schoolId);
    }

    public static function isAllowed(mixed $value): bool
    {
        return is_string($value) && in_array($value, self::ALLOWED, true);
    }

    public static function options(): array
    {
        return [
            self::NAME => 'Nama (A–Z)',
            self::NIS => 'NIS (kecil ke besar)',
        ];
    }

    public static function label(string $order): string
    {
        return self::options()[$order] ?? self::options()[self::NAME];
    }

    // ── Penerapan ke query ──────────────────────────────────────────

    /**
     * Terapkan urutan ke query santri.
     *
     * Urutan NIS memakai angka, bukan teks: NIS di sistem ini panjangnya
     * campur (4–18 karakter), sehingga pengurutan teks menaruh "10" sebelum
     * "9". Santri tanpa NIS diletakkan paling akhir, lalu diurutkan nama.
     */
    public static function apply(Builder $query, ?string $order = null): Builder
    {
        $order = self::isAllowed($order) ? $order : self::NAME;

        if ($order === self::NIS) {
            return self::applyNisOrder($query);
        }

        return $query->orderBy('name');
    }

    /**
     * Urutkan koleksi yang sudah terlanjur diambil dari database
     * (mis. baris penghuni asrama) dengan aturan yang sama.
     *
     * @param  \Illuminate\Support\Collection  $items
     * @param  callable(mixed): (object|null)  $studentOf  pengambil objek santri per baris
     */
    public static function sortCollection($items, callable $studentOf, ?string $order = null)
    {
        $order = self::isAllowed($order) ? $order : self::NAME;

        if ($order !== self::NIS) {
            return $items->sortBy(fn ($item) => mb_strtolower((string) ($studentOf($item)?->name ?? '')))->values();
        }

        return $items->sortBy(function ($item) use ($studentOf) {
            $student = $studentOf($item);
            $nis = trim((string) ($student?->nis ?? ''));

            // Panjang key harus SELALU sama: PHP membandingkan array lebih
            // dulu menurut jumlah elemen.
            return [
                $nis === '' ? 1 : 0,
                ...self::numericKey($nis),
                $nis,
                mb_strtolower((string) ($student?->name ?? '')),
            ];
        })->values();
    }

    /**
     * Kunci angka dari sebuah NIS, sejajar dengan nisOrderExpressions().
     *
     * @return array{0: float, 1: int}
     */
    public static function numericKey(string $nis): array
    {
        return [
            // Seluruh digit NIS sebagai angka: 9 < 10 < 100.
            (float) (preg_replace('/\D/', '', $nis) ?: 0),
            strlen($nis),
        ];
    }

    /**
     * Potongan angka berurutan pada sebuah NIS ("AMPi 024001" → [24, 24001]).
     *
     * @return list<float>
     */
    public static function numericRuns(string $nis): array
    {
        preg_match_all('/\d+/', $nis, $matches);

        return array_map('floatval', $matches[0] ?: []);
    }

    private static function applyNisOrder(Builder $query): Builder
    {
        $nis = $query->getModel()->qualifyColumn('nis');

        foreach (self::nisOrderExpressions($query->getConnection()->getDriverName(), $nis) as $expression) {
            $query->orderByRaw($expression);
        }

        return $query;
    }

    /**
     * Daftar ekspresi ORDER BY untuk urutan NIS.
     *
     * NIS di sistem ini panjangnya campur (4–18 karakter) dan sebagian
     * berawalan huruf ("AMPi 025001", "2024/AB/001"), sehingga urutan teks
     * biasa menaruh "10" sebelum "9".
     *
     * Pendekatannya: buang semua non-angka lalu bandingkan sebagai angka.
     * Cara ini portabel (SQLite produksi & MySQL lokal) dan — penting —
     * tidak memotong angka di tengah, karena pemotongan tetap membuat
     * "024001" terbelah menjadi 240 dan 1 sehingga tahun yang berbeda
     * bertabrakan dan urutannya jadi tidak pasti.
     *
     * @return list<string>
     */
    public static function nisOrderExpressions(string $driver, string $nis): array
    {
        $digits = self::numericRunSql($driver, $nis);
        // REAL tidak ada di MariaDB; DECIMAL(30,0) menampung 18 digit dan
        // juga dipahami SQLite.
        $numericType = $driver === 'sqlite' ? 'REAL' : 'DECIMAL(30,0)';

        return [
            // 0) Santri tanpa NIS diletakkan paling akhir.
            "CASE WHEN {$nis} IS NULL OR TRIM({$nis}) = '' THEN 1 ELSE 0 END",
            // 1) Angka penuh NIS (desimal lebar agar 18 digit tetap terurut).
            "COALESCE(CAST({$digits} AS {$numericType}), 0)",
            // 2) NIS kembar (NULL dan '') disamakan agar diurut nama.
            "CASE WHEN {$nis} IS NULL OR TRIM({$nis}) = '' THEN '' ELSE {$nis} END",
            'name',
        ];
    }

    /**
     * SQL untuk mengambil hanya digit dari kolom NIS ("" bila tanpa angka).
     *
     * MariaDB (dipakai di mesin pengembang) tidak punya REGEXP_REPLACE,
     * sehingga jalur MySQL sengaja dibangun dari REPLACE biasa agar SQL-nya
     * sama di semua server MySQL/MariaDB.
     */
    private static function numericRunSql(string $driver, string $nis): string
    {
        $stripped = $nis;

        foreach ([' ', '/', '-', '.', ',', '#', '_'] as $char) {
            $stripped = "REPLACE({$stripped}, '{$char}', '')";
        }

        return $stripped;
    }
}
