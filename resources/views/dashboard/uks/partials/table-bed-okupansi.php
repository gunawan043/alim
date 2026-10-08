<?php

use Illuminate\Support\Facades\DB;

$gender = $this->getUksGender($user);

$beds = DB::table('uks_beds as b')
    ->when($gender !== null, fn ($q) => $q->where('b.gender', $gender))
    ->orderBy('b.sort_order')
    ->orderBy('b.bed_number')
    ->limit(15)
    ->get(['b.id', 'b.bed_number', 'b.room', 'b.building', 'b.gender', 'b.status']);

$assignments = DB::table('uks_bed_assignments as a')
    ->join('uks_patients as p', 'p.id', '=', 'a.patient_id')
    ->join('students as s', 's.id', '=', 'p.student_id')
    ->whereIn('a.bed_id', $beds->pluck('id')->all() ?: ['-'])
    ->where('a.status', 'assigned')
    ->whereNull('a.released_at')
    ->get(['a.bed_id', 's.name as santri'])
    ->keyBy('bed_id');

$items = $beds->map(fn ($bed) => (object) [
    'bed'    => $bed->bed_number,
    'ruang'  => trim(($bed->building ?? '') . ' ' . ($bed->room ?? '')) ?: '—',
    'gender' => $bed->gender === 'L' ? 'Putra' : 'Putri',
    'status' => $bed->status,
    'pasien' => $assignments[$bed->id]->santri ?? null,
])->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Okupansi Bed UKS',
    'items' => $items,
];
