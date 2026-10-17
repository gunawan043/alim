<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$items = [];

try {
    $items = DB::table('extracurriculars as e')
        ->leftJoin('users as u', 'u.id', '=', 'e.supervisor_id')
        ->where('e.is_active', 1)
        ->when($schoolId, fn ($q) => $q->where('e.school_id', $schoolId))
        ->orderByRaw('COALESCE(e.schedule_day, 99)')
        ->limit(10)
        ->get([
            'e.name', 'e.category', 'e.schedule_day',
            'e.schedule_time_start', 'e.schedule_time_end', 'e.room',
            'u.name as pembina',
        ])
        ->all();
} catch (\Throwable $e) {
    Log::warning('Widget table-jadwal-ekskul-minggu-ini: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Jadwal Ekstrakurikuler',
    'items' => $items,
];
