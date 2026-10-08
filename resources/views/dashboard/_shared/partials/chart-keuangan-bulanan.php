<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$rows = collect();

try {
    $start = now()->subMonths(5)->startOfMonth();

    $rows = DB::table('payroll')
        ->where(function ($q) use ($start) {
            $q->where('tahun', '>', $start->year)
                ->orWhere(function ($sub) use ($start) {
                    $sub->where('tahun', $start->year)->where('bulan', '>=', $start->month);
                });
        })
        ->selectRaw('tahun, bulan, SUM(gaji_bersih) as total')
        ->groupBy('tahun', 'bulan')
        ->get()
        ->keyBy(fn ($r) => $r->tahun . '-' . str_pad((string) $r->bulan, 2, '0', STR_PAD_LEFT));
} catch (\Throwable $e) {
    Log::warning('Widget chart-keuangan-bulanan: ' . $e->getMessage());
}

$categories = [];
$data = [];

foreach (range(5, 0) as $i) {
    $month = now()->subMonths($i);
    $key = $month->year . '-' . $month->format('m');
    $categories[] = $month->translatedFormat('M Y');
    $data[] = (float) ($rows[$key]->total ?? 0);
}

return [
    '_col'   => 'col-xl-6 col-md-12',
    'key'    => 'keuangan-bulanan',
    'label'  => 'Realisasi Gaji GTK (6 Bulan)',
    'badge'  => 'Payroll',
    'type'   => 'area',
    'series' => [['name' => 'Total Gaji', 'data' => $data]],
    'options' => [
        'xaxis'      => ['categories' => $categories],
        'stroke'     => ['curve' => 'smooth', 'width' => 3],
        'fill'       => ['type' => 'gradient', 'gradient' => ['opacityFrom' => 0.35, 'opacityTo' => 0.05]],
        'colors'     => ['#0ab39c'],
        'dataLabels' => ['enabled' => false],
    ],
];
