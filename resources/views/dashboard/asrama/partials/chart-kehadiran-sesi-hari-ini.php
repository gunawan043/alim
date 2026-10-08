<?php

use Illuminate\Support\Facades\DB;

$sessions = ['subuh', 'pagi', 'siang', 'sore', 'isya', 'malam'];

$rows = $this->scopeAsramaQuery(
    DB::table('dormitory_attendances')->where('attendance_date', today()),
    $user
)->selectRaw("session, SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir, COUNT(*) as total")
    ->groupBy('session')
    ->get()
    ->keyBy('session');

$data = [];
foreach ($sessions as $session) {
    $row = $rows->get($session);
    $data[] = ($row && $row->total > 0) ? round($row->hadir / $row->total * 100, 1) : 0;
}

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-6 col-md-12',
    'key'    => 'kehadiran-sesi',
    'label'  => 'Kehadiran per Sesi Hari Ini',
    'badge'  => '6 sesi',
    'type'   => 'bar',
    'series' => [['name' => 'Kehadiran (%)', 'data' => $data]],
    'options' => [
        'xaxis'       => ['categories' => array_map('ucfirst', $sessions)],
        'yaxis'       => ['max' => 100],
        'colors'      => ['#405189'],
        'dataLabels'  => ['enabled' => false],
        'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '45%']],
    ],
];
