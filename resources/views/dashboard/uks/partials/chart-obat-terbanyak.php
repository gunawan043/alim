<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$rows = $this->applyUksScope(
    DB::table('uks_medication_logs as m')
        ->join('uks_patients as p', 'p.id', '=', 'm.patient_id')
        ->where('m.given_at', '>=', now()->subDays(30))
        ->when($schoolId, fn ($q) => $q->where('p.school_id', $schoolId)),
    $user,
    'p.student_id'
)
    ->selectRaw("COALESCE(NULLIF(m.medicine_name, ''), 'Tidak dicatat') as obat, COUNT(*) as total")
    ->groupBy('obat')
    ->orderByDesc('total')
    ->limit(6)
    ->get();

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-6 col-md-12',
    'key'    => 'obat-terbanyak',
    'label'  => 'Obat Paling Sering (30 Hari)',
    'type'   => 'bar',
    'series' => [['name' => 'Pemberian', 'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all()]],
    'options' => [
        'plotOptions' => ['bar' => ['horizontal' => true, 'borderRadius' => 4, 'columnWidth' => '55%']],
        'xaxis'       => ['categories' => $rows->pluck('obat')->all()],
        'colors'      => ['#0ab39c'],
        'dataLabels'  => ['enabled' => true],
    ],
];
