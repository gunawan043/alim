<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Master Jam Pelajaran default per sekolah (idempoten, upsert per slot):
 *  - SD       : Senin–Jumat (9 jam/hari = 45 slot mengajar)
 *  - SMP/SMA  : Senin–Sabtu (9 jam/hari = 54 slot mengajar)
 *
 * Template: 9 jam pelajaran dengan jeda 10.10–10.40.
 */
class ClassScheduleSlotSeeder extends Seeder
{
    public function run(): void
    {
        $totalCreated = 0;
        $totalUpdated = 0;

        foreach (School::orderBy('name')->get() as $school) {
            $isSd = strtolower((string) $school->school_level) === 'sd';
            $days = $isSd ? [1, 2, 3, 4, 5] : [1, 2, 3, 4, 5, 6];

            foreach ($days as $day) {
                foreach ($this->gridForDay($day) as $slot) {
                    $existing = \DB::table('class_schedule_slots')
                        ->where('school_id', $school->id)
                        ->where('day_of_week', $day)
                        ->where('slot_number', $slot['number'])
                        ->first();

                    if ($existing) {
                        \DB::table('class_schedule_slots')
                            ->where('id', $existing->id)
                            ->update([
                                'label' => $slot['label'],
                                'time_start' => $slot['start'],
                                'time_end' => $slot['end'],
                                'is_break' => $slot['is_break'] ? 1 : 0,
                                'is_active' => 1,
                                'updated_at' => now(),
                            ]);
                        $totalUpdated++;
                        continue;
                    }

                    \DB::table('class_schedule_slots')->insert([
                        'id' => (string) Str::uuid(),
                        'school_id' => $school->id,
                        'slot_number' => $slot['number'],
                        'label' => $slot['label'],
                        'time_start' => $slot['start'],
                        'time_end' => $slot['end'],
                        'is_break' => $slot['is_break'] ? 1 : 0,
                        'day_of_week' => $day,
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $totalCreated++;
                }
            }
        }

        $this->command?->info("Master jam pelajaran: {$totalCreated} slot baru, {$totalUpdated} diperbarui.");
    }

    /**
     * Grid satu hari: 9 jam pelajaran dengan jeda 10.10–10.40.
     *
     * @return array<int, array{number:int,label:string,start:string,end:string,is_break:bool}>
     */
    private function gridForDay(int $day): array
    {
        return [
            ['number' => 1, 'label' => 'Jam 1', 'start' => '07:30:00', 'end' => '08:10:00', 'is_break' => false],
            ['number' => 2, 'label' => 'Jam 2', 'start' => '08:10:00', 'end' => '08:50:00', 'is_break' => false],
            ['number' => 3, 'label' => 'Jam 3', 'start' => '08:50:00', 'end' => '09:30:00', 'is_break' => false],
            ['number' => 4, 'label' => 'Jam 4', 'start' => '09:30:00', 'end' => '10:10:00', 'is_break' => false],
            ['number' => 5, 'label' => 'Jam 5', 'start' => '10:40:00', 'end' => '11:15:00', 'is_break' => false],
            ['number' => 6, 'label' => 'Jam 6', 'start' => '11:15:00', 'end' => '11:50:00', 'is_break' => false],
            ['number' => 7, 'label' => 'Jam 7', 'start' => '11:50:00', 'end' => '12:25:00', 'is_break' => false],
            ['number' => 8, 'label' => 'Jam 8', 'start' => '12:25:00', 'end' => '13:00:00', 'is_break' => false],
            ['number' => 9, 'label' => 'Jam 9', 'start' => '13:00:00', 'end' => '13:30:00', 'is_break' => false],
            ['number' => 10, 'label' => 'Istirahat', 'start' => '10:10:00', 'end' => '10:40:00', 'is_break' => true],
        ];
    }
}
