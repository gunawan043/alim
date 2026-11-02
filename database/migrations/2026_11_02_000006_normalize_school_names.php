<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menormalkan penulisan nama satuan pendidikan dan asrama.
 *
 * Data lama tersimpan dalam Title Case ("Sd It Putri Abu Hurairah Mataram")
 * padahal singkatan jenjang harus kapital ("SD IT Putri Abu Hurairah Mataram").
 * Nama kanonik diambil dari work_units (sumber yang sudah benar sejak seeder).
 */
return new class extends Migration
{
    /** Singkatan jenjang yang wajib kapital. */
    private const ACRONYMS = [
        'sd' => 'SD',
        'smp' => 'SMP',
        'sma' => 'SMA',
        'smk' => 'SMK',
        'mi' => 'MI',
        'mts' => 'MTs',
        'ma' => 'MA',
        'tk' => 'TK',
        'paud' => 'PAUD',
        'it' => 'IT',
        'pps' => 'PPS',
        'pgmi' => 'PGMI',
    ];

    public function up(): void
    {
        $this->normalizeSchools();
        $this->normalizeDormitories();
        $this->normalizeBuildingNotes();
    }

    public function down(): void
    {
        // Perbaikan data tidak dibalik: penulisan lama memang salah.
    }

    private function normalizeSchools(): void
    {
        foreach (DB::table('schools')->get(['id', 'school_code', 'name', 'kop_nama']) as $school) {
            $canonical = $this->canonicalName($school->school_code, $school->name);

            if ($canonical === null || $canonical === $school->name) {
                continue;
            }

            $update = ['name' => $canonical, 'updated_at' => now()];

            // Kop surat ikut diperbaiki bila masih menyalin nama lama.
            if ($school->kop_nama !== null && trim((string) $school->kop_nama) === trim((string) $school->name)) {
                $update['kop_nama'] = $canonical;
            }

            DB::table('schools')->where('id', $school->id)->update($update);

            // Unit kerja dengan kode sama juga diselaraskan.
            DB::table('work_units')
                ->where('code', $school->school_code)
                ->where('name', $school->name)
                ->update(['name' => $canonical, 'updated_at' => now()]);
        }
    }

    private function normalizeDormitories(): void
    {
        foreach (DB::table('dormitories')->get(['id', 'work_unit_id', 'name']) as $dorm) {
            $unitName = $dorm->work_unit_id
                ? DB::table('work_units')->where('id', $dorm->work_unit_id)->value('name')
                : null;

            $canonical = filled($unitName) && $this->normalizeCase($unitName) === $this->normalizeCase($dorm->name)
                ? $unitName
                : $this->normalizeCase($dorm->name);

            if ($canonical === $dorm->name) {
                continue;
            }

            DB::table('dormitories')->where('id', $dorm->id)->update([
                'name' => $canonical,
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Catatan gedung hasil seeder masih menyebut nama asrama dalam Title Case.
     */
    private function normalizeBuildingNotes(): void
    {
        $pattern = '/\b(Sd|Smp|Sma|Ma|Pps|Paud) (It|Plus|Diniyah)\b/';

        DB::table('asset_buildings')->select(['id', 'notes'])->chunkById(200, function ($rows) use ($pattern) {
            foreach ($rows as $row) {
                if (blank($row->notes) || ! preg_match($pattern, $row->notes)) {
                    continue;
                }

                $fixed = $this->normalizeCase($row->notes);

                if ($fixed === $row->notes) {
                    continue;
                }

                DB::table('asset_buildings')->where('id', $row->id)->update([
                    'notes' => $fixed,
                    'updated_at' => now(),
                ]);
            }
        });
    }

    private function canonicalName(?string $code, ?string $current): ?string
    {
        if (blank($current)) {
            return null;
        }

        // 1. Utamakan nama dari unit kerja (sudah benar sejak seeder).
        if (filled($code)) {
            $unitName = DB::table('work_units')->where('code', $code)->value('name');

            if (filled($unitName) && $this->normalizeCase($unitName) === $this->normalizeCase($current)) {
                return $unitName;
            }
        }

        // 2. Perbaiki kapitalisasi singkatan pada nama yang ada.
        return $this->normalizeCase($current);
    }

    private function normalizeCase(string $text): string
    {
        $text = preg_replace_callback(
            '/\b([A-Za-z]{2,4})\b/u',
            function (array $match) {
                $lower = mb_strtolower($match[1]);

                return self::ACRONYMS[$lower] ?? $match[1];
            },
            $text
        );

        return trim((string) $text);
    }
};
