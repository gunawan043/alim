<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Pemetaan ayat → halaman dan baris Mushaf Madinah (Hafs).
 *
 * Data sumber: layout QPC2 Hafs pada proyek
 * https://github.com/zonetecde/mushaf-layout (604 halaman, 15 baris).
 * Yang disimpan hanya metadata posisi, bukan teks atau glyph mushaf.
 */
class TahfidzAyatPositionSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/tahfidz_quran_positions.json');

        if (! is_file($path)) {
            throw new \RuntimeException('Dataset posisi ayat tidak ditemukan: '.$path);
        }

        $positions = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $now = now();

        foreach (array_chunk($positions, 500) as $chunk) {
            $rows = array_map(fn (array $position) => [
                'surah' => $position['surah'],
                'ayah' => $position['ayah'],
                'page_start' => $position['page_start'],
                'line_start' => $position['line_start'],
                'page_end' => $position['page_end'],
                'line_end' => $position['line_end'],
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk);

            DB::table('tahfidz_ayat_positions')->upsert(
                $rows,
                ['surah', 'ayah'],
                ['page_start', 'line_start', 'page_end', 'line_end', 'updated_at']
            );
        }
    }
}
