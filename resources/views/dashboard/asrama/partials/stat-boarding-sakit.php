<?php

use Illuminate\Support\Facades\DB;

$count = $this->scopeAsramaQuery(
    DB::table('student_boarding_statuses')->where('status', 'AT_HOSPITAL'),
    $user
)->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Sakit / Rawat',
    'icon'  => 'ri-heart-pulse-line',
    'color' => $count > 0 ? 'danger' : 'success',
    'sub'   => 'Santri sakit / dirawat',
];
