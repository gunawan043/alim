<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = $this->applyUksScope(
    DB::table('uks_patients as p')
        ->join('students as s', 's.id', '=', 'p.student_id')
        ->where(function ($q) {
            $q->where('p.status', 'dirujuk')->orWhere('p.referred_to_faskes', 1);
        })
        ->when($schoolId, fn ($q) => $q->where('p.school_id', $schoolId)),
    $user,
    'p.student_id'
)
    ->orderByDesc('p.admitted_at')
    ->limit(10)
    ->get([
        's.name as santri', 'p.diagnosis', 'p.referral_reason', 'p.admitted_at', 'p.status',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Rujukan Terbaru',
    'items' => $items,
];
