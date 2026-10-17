<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$items = [];

try {
    $items = DB::table('supervisi')
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
        ->whereNull('deleted_at')
        ->whereIn('status', ['terjadwal', 'berlangsung'])
        ->orderBy('tanggal_supervisi')
        ->orderBy('jam_mulai')
        ->limit(10)
        ->get(['gtk_name', 'observer_name', 'mata_pelajaran', 'tanggal_supervisi', 'jam_mulai', 'jam_selesai', 'status'])
        ->all();
} catch (\Throwable $e) {
    Log::warning('Widget table-jadwal-supervisi: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Jadwal Supervisi',
    'items' => $items,
];
