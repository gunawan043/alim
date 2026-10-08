<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$query = DB::table('uks_treatments as t')
    ->join('uks_patients as p', 'p.id', '=', 't.patient_id')
    ->whereDate('t.created_at', today())
    ->when($schoolId, fn ($q) => $q->where('p.school_id', $schoolId));

$count = $this->applyUksScope($query, $user, 'p.student_id')->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Tindakan Hari Ini',
    'icon'  => 'ri-first-aid-kit-line',
    'color' => 'success',
    'sub'   => 'Tindakan medis tercatat',
];
