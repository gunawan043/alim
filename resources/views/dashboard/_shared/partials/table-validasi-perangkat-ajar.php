<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$items = [];

try {
    $items = DB::table('teacher_admin_books as ab')
        ->join('users as u', 'u.id', '=', 'ab.teacher_id')
        ->leftJoin('subjects as s', 's.id', '=', 'ab.subject_id')
        ->leftJoin('study_groups as sg', 'sg.id', '=', 'ab.study_group_id')
        ->when($schoolId, fn ($q) => $q->where('ab.school_id', $schoolId))
        ->where('ab.is_active', 1)
        ->whereNotExists(function ($q) {
            $q->select(DB::raw(1))
                ->from('admin_nilai_sumatif as ns')
                ->whereColumn('ns.admin_book_id', 'ab.id')
                ->whereNotNull('ns.nr_final');
        })
        ->orderBy('u.name')
        ->limit(10)
        ->get(['u.name as guru', 's.name as mapel', 'sg.name as kelas'])
        ->all();
} catch (\Throwable $e) {
    Log::warning('Widget table-validasi-perangkat-ajar: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Perangkat Ajar Perlu Validasi',
    'items' => $items,
];
