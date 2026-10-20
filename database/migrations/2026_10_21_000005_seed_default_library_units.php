<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('library_units') || DB::table('library_units')->exists()) {
            return;
        }

        $schools = DB::table('schools')->where('is_active', true)->get();
        if ($schools->isEmpty()) {
            return;
        }

        $pick = function (array $levels, ?string $gender = null) use ($schools) {
            return $schools->first(function ($school) use ($levels, $gender) {
                $level = strtolower((string) ($school->school_level ?? ''));
                $schoolGender = strtolower((string) ($school->school_gender ?? ''));
                return in_array($level, $levels, true) && ($gender === null || $schoolGender === $gender);
            });
        };

        $putra = $pick(['smp', 'sma'], 'putra') ?? $pick(['smp', 'sma']);
        $putri = $pick(['smp', 'sma'], 'putri') ?? $pick(['smp', 'sma']);
        $sd = $pick(['sd']);

        $now = now();
        $rows = [];
        foreach ([
            ['PERPUS-PUTRA', 'Perpustakaan Putra', $putra, 'L', 'sd_smp'],
            ['PERPUS-PUTRI', 'Perpustakaan Putri', $putri, 'P', 'sd_smp'],
            ['PERPUS-SD', 'Perpustakaan SD', $sd, 'mixed', 'sd'],
        ] as [$code, $name, $school, $gender, $level]) {
            if (! $school) {
                continue;
            }
            $rows[] = [
                'id' => (string) Str::uuid(),
                'work_unit_id' => $school->work_unit_id ?? null,
                'school_id' => $school->id,
                'code' => $code,
                'name' => $name,
                'gender_scope' => $gender,
                'level_scope' => $level,
                'location' => null,
                'is_active' => true,
                'notes' => 'Unit default dibuat otomatis. Silakan sesuaikan melalui menu Unit & Pengaturan.',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('library_units')->insert($rows);
        }
    }

    public function down(): void
    {
        // Unit default tidak dihapus otomatis agar data sirkulasi tidak ikut hilang.
    }
};
