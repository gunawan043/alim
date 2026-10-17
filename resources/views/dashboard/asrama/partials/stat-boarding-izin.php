<?php

use Illuminate\Support\Facades\DB;

$count = $this->scopeAsramaQuery(
    DB::table('student_boarding_statuses')->where('status', 'ON_LEAVE'),
    $user
)->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Izin Pulang',
    'icon'  => 'ri-walk-line',
    'color' => $count > 0 ? 'warning' : 'success',
    'sub'   => 'Santri sedang izin / pulang',
];
