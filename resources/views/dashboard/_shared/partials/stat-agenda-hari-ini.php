<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$count = 0;

try {
    $count = DB::table('agendas')
        ->whereNull('deleted_at')
        ->whereDate('start_datetime', today())
        ->where(function ($q) {
            $q->whereNull('status')->orWhereNotIn('status', ['cancelled', 'canceled', 'dibatalkan']);
        })
        ->when($schoolId, function ($q) use ($schoolId) {
            $q->where(function ($sub) use ($schoolId) {
                $sub->where('school_id', $schoolId)->orWhereNull('school_id');
            });
        })
        ->count();
} catch (\Throwable $e) {
    Log::warning('Widget stat-agenda-hari-ini: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Agenda Hari Ini',
    'icon'  => 'ri-calendar-event-line',
    'color' => 'primary',
    'sub'   => 'Kegiatan terjadwal hari ini',
];
