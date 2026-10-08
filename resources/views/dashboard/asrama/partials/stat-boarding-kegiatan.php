<?php

use Illuminate\Support\Facades\DB;

$count = $this->scopeAsramaQuery(
    DB::table('student_boarding_statuses')->where('status', 'OFFICIAL_ACTIVITY'),
    $user
)->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Kegiatan Resmi',
    'icon'  => 'ri-flag-2-line',
    'color' => 'info',
    'sub'   => 'Santri di kegiatan resmi',
];
