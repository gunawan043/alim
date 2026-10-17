<?php

use Illuminate\Support\Facades\DB;

$items = DB::table('recruitment_applications as a')
    ->leftJoin('recruitment_jobs as j', 'j.id', '=', 'a.recruitment_job_id')
    ->orderByDesc('a.tanggal_melamar')
    ->limit(10)
    ->get([
        'a.no_lamaran', 'j.judul as lowongan', 'a.status', 'a.status_akhir',
        'a.nilai_akhir', 'a.ranking', 'a.tanggal_melamar',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Lamaran Terbaru',
    'items' => $items,
];
