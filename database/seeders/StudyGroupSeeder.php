<?php

namespace Database\Seeders;

use App\Models\StudyGroup;
use App\Models\School;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\User;
use Illuminate\Database\Seeder;

class StudyGroupSeeder extends Seeder
{
    public function run(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (!$activeYear) {
            $this->command->warn('⚠️ Tidak ada tahun ajaran aktif. StudyGroupSeeder dilewati.');
            return;
        }

        $schools = School::with('workUnit')->get();
        $gtkUsers = User::whereHas('employment')
            ->whereHas('gtkWorkUnits.workUnit', fn($q) => $q->where('type', 'Unit Akademik'))
            ->with(['gtkWorkUnits.workUnit' => fn($q) => $q->where('type', 'Unit Akademik')])
            ->get()
            ->keyBy(fn($u) => $u->gtkWorkUnits->first()?->workUnit?->id);

        $rombelMap = [
            'sd'  => ['A', 'B'],
            'smp' => ['A', 'B', 'C'],
            'sma' => ['A', 'B', 'C'],
        ];

        $created = 0;
        foreach ($schools as $school) {
            $gradeLevels = GradeLevel::where('school_id', $school->id)->orderBy('level')->get();
            $levelLetters = $rombelMap[$school->school_level] ?? ['A'];

            foreach ($gradeLevels as $gl) {
                foreach ($levelLetters as $letter) {
                    $fullName = "{$gl->code}-{$letter}"; // e.g. "VII-A"
                    $exists = StudyGroup::where('school_id', $school->id)
                        ->where('academic_year_id', $activeYear->id)
                        ->where('name', $fullName)
                        ->exists();
                    if ($exists) continue;

                    $wuId = $school->work_unit_id;
                    $homeroomTeacherId = $gtkUsers->get($wuId)?->id ?? null;

                    StudyGroup::create([
                        'school_id'           => $school->id,
                        'academic_year_id'    => $activeYear->id,
                        'grade_level_id'      => $gl->id,
                        'homeroom_teacher_id' => $homeroomTeacherId,
                        'name'                => $fullName,
                        'code'                => $fullName,
                        'capacity'            => $school->school_level === 'smp' ? 32 : 36,
                        'room'                => "Ruang {$gl->level}{$letter}",
                        'curriculum_type'     => 'merdeka',
                        'shift'               => 'pagi',
                        'is_active'          => true,
                        'notes'               => null,
                    ]);
                    $created++;
                }
            }
        }

        $this->command->info("✅ StudyGroupSeeder selesai — Created: {$created}, Total: " . StudyGroup::count());
    }
}
