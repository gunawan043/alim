<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = DB::table('sanitation_inspections as s')
    ->leftJoin('users as u', 'u.id', '=', 's.inspected_by')
    ->when($schoolId, fn ($q) => $q->where('s.school_id', $schoolId))
    ->orderByDesc('s.inspection_date')
    ->limit(10)
    ->get([
        's.inspection_date', 's.location_type', 's.score', 's.is_passed',
        'u.name as inspektur', 's.follow_up_deadline',
    ])
    ->all();

return [
    "_type" => "table",
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Inspeksi Kebersihan Terbaru',
    'items' => $items,
];
