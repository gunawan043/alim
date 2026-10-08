<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);

$items = [];

try {
    $items = DB::table('guardian_complaints as g')
        ->leftJoin('students as s', 's.id', '=', 'g.student_id')
        ->when($schoolId, fn ($q) => $q->where('g.school_id', $schoolId))
        ->orderByDesc('g.created_at')
        ->limit(10)
        ->get([
            'g.subject as aduan',
            'g.category as kategori',
            's.name as santri',
            'g.priority as prioritas',
            'g.status',
            'g.created_at',
        ])
        ->all();
} catch (\Throwable $e) {
    Log::warning('Widget table-aduan-terbaru: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Aduan Terbaru',
    'items' => $items,
];
