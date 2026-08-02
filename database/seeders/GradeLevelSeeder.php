<?php

namespace Database\Seeders;

use App\Models\GradeLevel;
use App\Models\School;
use Illuminate\Database\Seeder;

class GradeLevelSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::with('workUnit')->get();

        foreach ($schools as $school) {
            $level = $school->school_level;
            $levels = match ($level) {
                'sd' => [1, 2, 3, 4, 5, 6],
                'smp' => [7, 8, 9],
                'sma' => [10, 11, 12],
                'smk' => [10, 11, 12],
                default => [],
            };

            foreach ($levels as $lvl) {
                $name = "Kelas {$lvl}";
                $code = match ($lvl) {
                    1 => 'I',  2 => 'II', 3 => 'III',
                    4 => 'IV', 5 => 'V',  6 => 'VI',
                    7 => 'VII', 8 => 'VIII', 9 => 'IX',
                    10 => 'X', 11 => 'XI', 12 => 'XII',
                    default => (string) $lvl,
                };

                GradeLevel::firstOrCreate(
                    ['school_id' => $school->id, 'level' => $lvl],
                    ['name' => $name, 'code' => $code, 'is_active' => true]
                );
            }
        }

        $this->command->info('✅ GradeLevelSeeder selesai — GradeLevels: '.GradeLevel::count());
    }
}
