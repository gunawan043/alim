<?php

use Illuminate\Support\Facades\DB;

$count = DB::table('kinerja_penilaian')->where('status', '!=', 'final')->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Penilaian Belum Final',
    'icon'  => 'ri-draft-line',
    'color' => $count > 0 ? 'warning' : 'success',
    'sub'   => 'Masih draft / proses',
];
