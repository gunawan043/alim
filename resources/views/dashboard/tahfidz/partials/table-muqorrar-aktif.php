<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = DB::table('tahfidz_muqorrars')
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->whereNotNull('bulan_kbm')
    ->orderByDesc('bulan_kbm_tahun')
    ->orderByDesc('bulan_kbm')
    ->limit(10)
    ->get([
        'package_name', 'grade_class', 'bulan_kbm', 'bulan_kbm_tahun',
        'target_bulanan_halaman', 'target_harian_baris', 'jumlah_hari_aktif',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Muqorrar Aktif',
    'items' => $items,
];
