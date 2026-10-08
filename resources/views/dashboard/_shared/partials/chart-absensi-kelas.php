<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

$rombel = $this->getHomeroomStudyGroup($user);

$categories = [];
$percent = [];

if ($rombel) {
    $days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->toDateString());

    $rows = DB::table('student_attendances')
        ->where('study_group_id', $rombel->id)
        ->whereIn('attendance_date', $days->all())
        ->selectRaw("attendance_date, SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir, COUNT(*) as total")
        ->groupBy('attendance_date')
        ->get()
        ->keyBy(fn ($r) => Carbon::parse($r->attendance_date)->toDateString());

    foreach ($days as $d) {
        $r = $rows->get($d);
        $categories[] = Carbon::parse($d)->translatedFormat('d M');
        $percent[] = ($r && $r->total > 0) ? round($r->hadir / $r->total * 100, 1) : 0;
    }
}

return [
    '_col'   => 'col-xl-6 col-md-12',
    'key'    => 'absensi-kelas',
    'label'  => 'Absensi Kelas (7 Hari)' . ($rombel ? ' — ' . $rombel->name : ''),
    'type'   => 'bar',
    'series' => [['name' => 'Kehadiran (%)', 'data' => $percent]],
    'options' => [
        'xaxis'      => ['categories' => $categories],
        'yaxis'      => ['max' => 100],
        'colors'     => ['#405189'],
        'dataLabels' => ['enabled' => false],
        'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '45%']],
    ],
];
