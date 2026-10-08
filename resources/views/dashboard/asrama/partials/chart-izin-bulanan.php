<?php

use Illuminate\Support\Facades\DB;

$categories = [];
$data = [];

for ($i = 5; $i >= 0; $i--) {
    $month = now()->subMonths($i);

    $count = $this->scopeAsramaQuery(
        DB::table('dormitory_permits')
            ->whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month),
        $user
    )->count();

    $categories[] = $month->translatedFormat('M Y');
    $data[] = $count;
}

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-8 col-md-12',
    'key'    => 'izin-bulanan',
    'label'  => 'Tren Pengajuan Izin (6 Bulan)',
    'type'   => 'bar',
    'series' => [['name' => 'Jumlah Izin', 'data' => $data]],
    'options' => [
        'xaxis'       => ['categories' => $categories],
        'colors'      => ['#f7b84b'],
        'dataLabels'  => ['enabled' => false],
        'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '45%']],
    ],
];
