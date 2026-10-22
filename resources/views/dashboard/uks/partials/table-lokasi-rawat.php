<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$statuses = [
    'menunggu_pemeriksaan',
    'sedang_ditangani',
    'observasi',
    'rawat_uks',
    'istirahat_di_uks',
    'dirujuk_ke_klinik',
    'dirujuk_ke_rumah_sakit',
];

$counts = DB::table('uks_patients')
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->whereIn('status', $statuses)
    ->selectRaw('care_location, COUNT(*) as total')
    ->groupBy('care_location')
    ->pluck('total', 'care_location');

$labels = [
    'asrama' => 'Dirawat di Asrama',
    'uks' => 'Dirawat di UKS / Ranjang',
    'sekolah' => 'Tetap ke Sekolah',
    'rumah' => 'Pulang / Di Rumah',
    'faskes' => 'Dirujuk ke Faskes',
    'lainnya' => 'Lainnya',
];

$items = [];
foreach ($labels as $location => $label) {
    $items[] = (object) [
        'lokasi' => $label,
        'jumlah' => (int) ($counts[$location] ?? 0),
    ];
}

return [
    '_col'  => 'col-xl-4 col-md-6',
    'label' => 'Lokasi Perawatan Santri',
    'items' => $items,
];
