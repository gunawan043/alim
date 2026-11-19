<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Menambahkan jenjang PPS Diniyah (Pondok Pesantren Salafiyah).
 *
 * PPS Diniyah menyelenggarakan pendidikan 6 tahun: Wustho (kelas 7–9) dan
 * Ulya (kelas 10–12). Sebelumnya sekolah PPS dicatat sebagai jenjang `sma`
 * sehingga hanya punya kelas 10–12 dan kelulusan diputuskan di kelas 9.
 *
 * Kolom `school_level` diubah dari enum menjadi varchar agar jenjang baru
 * (PPS, MI, MTs, MA, dan varian lain) tidak memerlukan migrasi enum lagi.
 * Perubahan ini portabel untuk MySQL maupun SQLite.
 */
return new class extends Migration
{
    /** Kelas PPS: Wustho 7–9, Ulya 10–12. */
    private const PPS_GRADE_LEVELS = [
        7 => ['Kelas 7 Wustho', 'PPS-7', 'D'],
        8 => ['Kelas 8 Wustho', 'PPS-8', 'D'],
        9 => ['Kelas 9 Wustho', 'PPS-9', 'D'],
        10 => ['Kelas 10 Ulya', 'PPS-10', 'E'],
        11 => ['Kelas 11 Ulya', 'PPS-11', 'F'],
        12 => ['Kelas 12 Ulya', 'PPS-12', 'F'],
    ];

    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('school_level', 20)->nullable()->change();
        });

        foreach ($this->ppsSchools() as $school) {
            DB::table('schools')
                ->where('id', $school->id)
                ->update(['school_level' => 'pps', 'updated_at' => now()]);

            $this->syncGradeLevels($school->id);
        }
    }

    public function down(): void
    {
        // Kolom tetap varchar: mengembalikannya ke enum akan menolak nilai
        // jenjang lain yang sudah tersimpan.
    }

    /**
     * Sekolah PPS dikenali dari kode unit kerja atau nama resminya.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function ppsSchools()
    {
        return DB::table('schools')
            ->where(function ($query) {
                $query->where('school_code', 'UAK-008')
                    ->orWhere('name', 'like', 'PPS%');
            })
            ->get(['id', 'name']);
    }

    private function syncGradeLevels(string $schoolId): void
    {
        $hasFase = Schema::hasColumn('grade_levels', 'fase');

        foreach (self::PPS_GRADE_LEVELS as $level => [$name, $code, $fase]) {
            $existing = DB::table('grade_levels')
                ->where('school_id', $schoolId)
                ->where('level', $level)
                ->first();

            $attributes = array_filter([
                'name' => $name,
                'code' => $code,
                'fase' => $hasFase ? $fase : null,
            ], static fn ($value) => $value !== null);

            if ($existing) {
                DB::table('grade_levels')->where('id', $existing->id)->update(
                    $attributes + ['updated_at' => now()]
                );

                continue;
            }

            DB::table('grade_levels')->insert($attributes + [
                'id' => (string) Str::uuid(),
                'school_id' => $schoolId,
                'level' => $level,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
