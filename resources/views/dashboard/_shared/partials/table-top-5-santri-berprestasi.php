<?php

use Illuminate\Support\Facades\DB;

$rombel = $this->getHomeroomStudyGroup($user);
$items = [];

if ($rombel) {
    $items = DB::table('student_achievements as a')
        ->join('students as s', 's.id', '=', 'a.student_id')
        ->join('student_class_histories as h', function ($join) use ($rombel) {
            $join->on('h.student_id', '=', 'a.student_id')
                ->where('h.study_group_id', '=', $rombel->id)
                ->where('h.is_active', 1);
        })
        ->orderByDesc('a.event_date')
        ->limit(5)
        ->get(['s.name', 'a.event_name', 'a.achievement_type', 'a.level', 'a.position', 'a.event_date'])
        ->all();
}

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Top 5 Prestasi Santri Kelas',
    'items' => $items,
];
