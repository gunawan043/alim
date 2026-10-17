<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$gender = DB::table('students')
    ->where('status', 'active')
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->selectRaw('gender, COUNT(*) as total')
    ->groupBy('gender')
    ->pluck('total', 'gender');

$l = (int) ($gender['L'] ?? $gender['laki-laki'] ?? 0);
$p = (int) ($gender['P'] ?? $gender['perempuan'] ?? 0);

return [
    '_col'   => 'col-xl-4 col-md-6',
    'key'    => 'gender-santri',
    'label'  => 'Komposisi Gender Santri',
    'type'   => 'donut',
    'series' => [$l, $p],
    'labels' => ['Laki-laki', 'Putri'],
    'colors' => ['#405189', '#f06548'],
    'options' => [
        'legend'      => ['position' => 'bottom'],
        'plotOptions' => ['pie' => ['donut' => ['size' => '62%']]],
    ],
];
