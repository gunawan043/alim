<?php

use Illuminate\Support\Facades\DB;

$rombel = $this->getHomeroomStudyGroup($user);
$items = [];

if ($rombel) {
    $items = DB::table('violation_points as v')
        ->join('students as s', 's.id', '=', 'v.student_id')
        ->where('v.study_group_id', $rombel->id)
        ->orderByDesc('v.violation_date')
        ->limit(10)
        ->get(['s.name', 'v.violation_type', 'v.points', 'v.violation_date', 'v.action_taken'])
        ->all();
}

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Pelanggaran Kelas',
    'items' => $items,
];
