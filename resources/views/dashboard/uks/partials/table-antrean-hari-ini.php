<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = $this->applyUksScope(
    DB::table('uks_patients as p')
        ->join('students as s', 's.id', '=', 'p.student_id')
        ->whereDate('p.queue_date', today())
        ->when($schoolId, fn ($q) => $q->where('p.school_id', $schoolId)),
    $user,
    'p.student_id'
)
    ->orderByRaw("CASE p.triage_level WHEN 'P1' THEN 1 WHEN 'P2' THEN 2 ELSE 3 END")
    ->orderBy('p.queue_number')
    ->limit(10)
    ->get([
        'p.queue_number', 's.name as santri', 'p.triage_level',
        'p.chief_complaint', 'p.queue_status', 'p.arrived_at',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Antrean Klinik Hari Ini',
    'items' => $items,
];
