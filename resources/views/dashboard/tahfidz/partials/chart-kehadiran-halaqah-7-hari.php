<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);
$days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->toDateString());

$rows = $this->scopeTahfidzQuery(
    DB::table('tahfidz_group_attendances as a')
        ->when($schoolId, function ($q) use ($schoolId) {
            $q->whereExists(fn ($sub) => $sub->select(DB::raw(1))
                ->from('tahfidz_groups as g')
                ->whereColumn('g.id', 'a.tahfidz_group_id')
                ->where('g.school_id', $schoolId));
        })
        ->whereIn('a.attendance_date', $days->all()),
    $user,
    'a.tahfidz_group_id'
)->selectRaw("a.attendance_date, SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) as hadir, COUNT(*) as total")
    ->groupBy('a.attendance_date')
    ->get()
    ->keyBy(fn ($r) => Carbon::parse($r->attendance_date)->toDateString());

$categories = [];
$percent = [];

foreach ($days as $d) {
    $r = $rows->get($d);
    $categories[] = Carbon::parse($d)->translatedFormat('d M');
    $percent[] = ($r && $r->total > 0) ? round($r->hadir / $r->total * 100, 1) : 0;
}

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-6 col-md-12',
    'key'    => 'kehadiran-halaqah-7-hari',
    'label'  => 'Kehadiran Halaqah (7 Hari)',
    'type'   => 'line',
    'series' => [['name' => 'Kehadiran (%)', 'data' => $percent]],
    'options' => [
        'xaxis'      => ['categories' => $categories],
        'yaxis'      => ['max' => 100],
        'colors'     => ['#299cdb'],
        'dataLabels' => ['enabled' => false],
    ],
];
