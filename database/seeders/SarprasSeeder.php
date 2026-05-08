<?php

namespace Database\Seeders;

use App\Models\AssetRoom;
use App\Models\StudyGroup;
use Illuminate\Database\Seeder;

class SarprasSeeder extends Seeder
{
    public function run(): void
    {
        $count = 0;

        StudyGroup::withoutGlobalScope('school_context')
            ->whereNotNull('room')
            ->where('room', '!=', '')
            ->chunk(100, function ($studyGroups) use (&$count) {
                foreach ($studyGroups as $sg) {
                    if ($sg->school_id && blank(AssetRoom::where('study_group_id', $sg->id)->exists())) {
                        AssetRoom::create([
                            'school_id'      => $sg->school_id,
                            'work_unit_id'   => $sg->school?->work_unit_id,
                            'study_group_id' => $sg->id,
                            'room_code'      => 'RG-' . strtoupper(substr(md5($sg->id), 0, 6)),
                            'room_name'      => $sg->full_name,
                            'room_type'      => 'kelas',
                            'capacity'       => $sg->capacity,
                            'condition'      => 'baik',
                            'is_bookable'    => false,
                            'is_active'      => $sg->is_active,
                            'notes'          => 'Ruang Kelas — Rombongan Belajar',
                        ]);
                        $count++;
                    }
                }
            });

        $this->command->info("SarprasSeeder: {$count} ruang kelas di-sync ke asset_rooms.");
    }
}
