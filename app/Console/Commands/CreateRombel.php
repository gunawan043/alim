<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\StudyGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateRombel extends Command
{
    protected $signature = 'create:rombel {--force}';
    protected $description = 'Buat rombel untuk 4 sekolah (SD IT Putri, SD IT Putra, SMP IT Putra, SMP IT Putri)';

    public function handle(): int
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (!$activeYear) {
            $this->error('❌ Tidak ada tahun ajaran aktif.');
            return self::FAILURE;
        }

        $this->info("Tahun ajaran aktif: {$activeYear->name} ({$activeYear->id})");
        $this->line('');

        $rombelMap = [
            'SD IT Putri Abu Hurairah Mataram' => [
                1 => ['A', 'B', 'C', 'D'],
                2 => ['A', 'B'],
                3 => ['C'],
                4 => ['A', 'B', 'C'],
                5 => ['A', 'B', 'C'],
                6 => ['A', 'B', 'C'],
            ],
            'SD IT Putra Abu Hurairah Mataram' => [
                1 => ['A', 'B', 'C'],
                2 => ['A', 'B'],
                3 => ['C'],
                4 => ['A', 'B', 'C'],
                5 => ['A', 'B', 'C'],
                6 => ['A', 'B', 'C'],
            ],
            'SMP IT Putra Abu Hurairah Mataram' => [
                7 => ['A', 'B', 'C', 'D', 'E', 'F'],
                8 => ['A', 'B', 'C', 'D', 'E'],
                9 => ['A', 'B', 'C', 'D', 'E'],
            ],
            'SMP IT Putri Abu Hurairah Mataram' => [
                7 => ['A', 'B', 'C', 'D', 'E'],
                8 => ['A', 'B', 'C', 'D'],
                9 => ['A', 'B', 'C', 'D', 'E'],
            ],
        ];

        $schoolLevelCapacity = [
            'sd'  => 36,
            'smp' => 32,
        ];

        $schoolRoman = [
            'sd'  => [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI'],
            'smp' => [7 => 'VII', 8 => 'VIII', 9 => 'IX'],
        ];

        $created = 0;
        $skipped = 0;

        foreach ($rombelMap as $schoolName => $gradeMap) {
            $school = School::where('name', $schoolName)->first();
            if (!$school) {
                $this->warn("⚠️ Sekolah tidak ditemukan: {$schoolName}");
                continue;
            }

            $this->info("🏫 {$schoolName}");
            $this->line("   Level  Rombel                          → Detail");

            foreach ($gradeMap as $level => $letters) {
                $gradeLevel = GradeLevel::where('school_id', $school->id)
                    ->where('level', $level)
                    ->first();

                if (!$gradeLevel) {
                    $this->warn("   ⚠️ GradeLevel level={$level} tidak ada di {$schoolName}, dilewati.");
                    continue;
                }

                $roman = $schoolRoman[$school->school_level][$level] ?? (string) $level;
                $capacity = $schoolLevelCapacity[$school->school_level] ?? 36;

                foreach ($letters as $letter) {
                    $fullName = "{$roman}-{$letter}";

                    $exists = StudyGroup::where('school_id', $school->id)
                        ->where('academic_year_id', $activeYear->id)
                        ->where('name', $fullName)
                        ->exists();

                    if ($exists) {
                        $this->line("   Kelas {$level}  {$fullName}              → <fg=yellow>SUDAH ADA (dilewati)</>");
                        $skipped++;
                        continue;
                    }

                    StudyGroup::create([
                        'id'                   => (string) Str::uuid(),
                        'school_id'            => $school->id,
                        'academic_year_id'     => $activeYear->id,
                        'grade_level_id'       => $gradeLevel->id,
                        'homeroom_teacher_id'  => null,
                        'name'                 => $fullName,
                        'code'                 => $fullName,
                        'capacity'             => $capacity,
                        'room'                 => "Ruang {$level}{$letter}",
                        'curriculum_type'      => 'merdeka',
                        'shift'                => 'pagi',
                        'is_active'           => true,
                        'notes'                => null,
                    ]);

                    $this->line("   Kelas {$level}  {$fullName}              → <fg=green>✅ DIBUAT</>");
                    $created++;
                }
            }
            $this->line('');
        }

        $total = StudyGroup::where('academic_year_id', $activeYear->id)->count();

        $this->line('═══════════════════════════════════════════════════════');
        $this->info("✅ Selesai! Dibuat: {$created} | Sudah ada: {$skipped} | Total rombel aktif: {$total}");
        $this->line('═══════════════════════════════════════════════════════');

        return self::SUCCESS;
    }
}