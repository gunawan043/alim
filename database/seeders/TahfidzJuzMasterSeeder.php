<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Pembagian 30 juz standar Mushaf Madinah (604 halaman).
 * Juz 1–29 masing-masing 20 halaman; juz 30 sebanyak 23 halaman.
 * Nilai ini dapat disesuaikan di tabel tahfidz_juz_master tanpa mengubah kode.
 */
class TahfidzJuzMasterSeeder extends Seeder
{
    public function run(): void
    {
        for ($juz = 1; $juz <= 30; $juz++) {
            $start = 1 + (($juz - 1) * 20);
            $end = $juz === 30 ? 604 : $start + 19;

            DB::table('tahfidz_juz_master')->updateOrInsert(
                ['id' => $juz],
                [
                    'juz_number' => $juz,
                    'name' => 'Juz '.$juz,
                    'halaman_start' => $start,
                    'halaman_end' => $end,
                    'total_halaman' => $end - $start + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
