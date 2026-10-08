<?php

use Illuminate\Support\Facades\DB;

$count = $this->scopeAsramaQuery(
    DB::table('dormitory_inventories')->whereIn('condition', ['rusak', 'hilang']),
    $user
)->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Inventaris Bermasalah',
    'icon'  => 'ri-tools-line',
    'color' => $count > 0 ? 'warning' : 'success',
    'sub'   => 'Kondisi rusak / hilang',
];
