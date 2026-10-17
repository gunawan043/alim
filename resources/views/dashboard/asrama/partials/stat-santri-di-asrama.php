<?php

use Illuminate\Support\Facades\DB;

$count = $this->scopeAsramaQuery(
    DB::table('student_boarding_statuses')->where('status', 'IN_DORM'),
    $user
)->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Di Asrama',
    'icon'  => 'ri-home-4-line',
    'color' => 'success',
    'sub'   => 'Status santri saat ini',
];
