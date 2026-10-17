<?php

use Illuminate\Support\Facades\DB;

$count = DB::table('institution_decrees')
    ->whereNull('deleted_at')
    ->whereNotNull('end_date')
    ->whereDate('end_date', '<=', now()->addDays(60))
    ->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'SK Akan Berakhir',
    'icon'  => 'ri-file-warning-line',
    'color' => $count > 0 ? 'danger' : 'success',
    'sub'   => 'Habis / ≤ 60 hari lagi',
];
