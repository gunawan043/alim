<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$row = $this->scopeTahfidzQuery(
    DB::table('tahfidz_mutabaah as m')
        ->when($schoolId, function ($q) use ($schoolId) {
            $q->whereExists(fn ($sub) => $sub->select(DB::raw(1))
                ->from('tahfidz_groups as g')
                ->whereColumn('g.id', 'm.tahfidz_group_id')
                ->where('g.school_id', $schoolId));
        })
        ->where('m.record_date', '>=', now()->subDays(7)),
    $user,
    'm.tahfidz_group_id'
)->selectRaw('COUNT(*) as total,
    SUM(sholat_subuh) as subuh,
    SUM(sholat_dzuhur) as dzuhur,
    SUM(sholat_ashar) as ashar,
    SUM(sholat_maghrib) as maghrib,
    SUM(sholat_isya) as isya,
    SUM(sholat_tahajud) as tahajud,
    SUM(sholat_dhuha) as dhuha,
    SUM(puasa_sunnah) as puasa')
    ->first();

$total = max(1, (int) ($row->total ?? 0));

$data = [
    round(((int) ($row->subuh ?? 0)) / $total * 100),
    round(((int) ($row->dzuhur ?? 0)) / $total * 100),
    round(((int) ($row->ashar ?? 0)) / $total * 100),
    round(((int) ($row->maghrib ?? 0)) / $total * 100),
    round(((int) ($row->isya ?? 0)) / $total * 100),
    round(((int) ($row->tahajud ?? 0)) / $total * 100),
    round(((int) ($row->dhuha ?? 0)) / $total * 100),
    round(((int) ($row->puasa ?? 0)) / $total * 100),
];

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-8 col-md-12',
    'key'    => 'mutabaah-ibadah',
    'label'  => "Kepatuhan Ibadah Mutaba'ah (7 Hari)",
    'badge'  => 'persen',
    'type'   => 'bar',
    'series' => [['name' => 'Kepatuhan (%)', 'data' => $data]],
    'options' => [
        'xaxis'       => ['categories' => ['Subuh', 'Dzuhur', 'Ashar', 'Maghrib', 'Isya', 'Tahajud', 'Dhuha', 'Puasa']],
        'yaxis'       => ['max' => 100],
        'colors'      => ['#0ab39c'],
        'dataLabels'  => ['enabled' => false],
        'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '50%']],
    ],
];
