<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Resolusi posisi ayat pada Mushaf Madinah (604 halaman, 15 baris).
 *
 * Sumber data: layout QPC2 Hafs dari dataset mushaf-layout. Service ini
 * hanya membaca metadata posisi, bukan teks atau glyph mushaf.
 */
class QuranPositionService
{
    /** @var array<string, array<string, int>|null> */
    private array $cache = [];

    public function find(int $surah, int $ayah): ?array
    {
        if ($surah < 1 || $surah > 114 || $ayah < 1) {
            return null;
        }

        $key = $surah.':'.$ayah;
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        try {
            if (! Schema::hasTable('tahfidz_ayat_positions')) {
                return $this->cache[$key] = null;
            }

            $row = DB::table('tahfidz_ayat_positions')
                ->where('surah', $surah)
                ->where('ayah', $ayah)
                ->first([
                    'page_start', 'line_start', 'page_end', 'line_end',
                ]);
        } catch (Throwable) {
            return $this->cache[$key] = null;
        }

        if (! $row) {
            return $this->cache[$key] = null;
        }

        return $this->cache[$key] = [
            'surah' => $surah,
            'ayah' => $ayah,
            'page_start' => (int) $row->page_start,
            'line_start' => (int) $row->line_start,
            'page_end' => (int) ($row->page_end ?: $row->page_start),
            'line_end' => (int) ($row->line_end ?: $row->line_start),
        ];
    }

    public function range(
        int $surahStart,
        int $ayahStart,
        ?int $surahEnd = null,
        ?int $ayahEnd = null,
    ): ?array {
        $start = $this->find($surahStart, $ayahStart);

        if (! $start) {
            return null;
        }

        $surahEnd = $surahEnd ?: $surahStart;
        $ayahEnd = $ayahEnd ?: $ayahStart;
        $end = $this->find($surahEnd, $ayahEnd) ?: $start;

        return [
            'surah_start' => $surahStart,
            'ayah_start' => $ayahStart,
            'surah_end' => $surahEnd,
            'ayah_end' => $ayahEnd,
            'page_start' => $start['page_start'],
            'line_start' => $start['line_start'],
            'page_end' => $end['page_end'],
            'line_end' => $end['line_end'],
        ];
    }
}
