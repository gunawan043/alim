<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);

$items = [];

try {
    $items = DB::table('spp_bills as b')
        ->leftJoin('students as s', 's.id', '=', 'b.student_id')
        ->when($schoolId, fn ($q) => $q->where('b.school_id', $schoolId))
        ->orderByDesc('b.period')
        ->orderByDesc('b.created_at')
        ->limit(10)
        ->get([
            's.name as santri',
            'b.period as periode',
            'b.amount as tagihan_amount',
            'b.paid_amount as dibayar_amount',
            'b.status',
            'b.due_date',
        ])
        ->all();
} catch (\Throwable $e) {
    Log::warning('Widget table-spp-terbaru: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Tagihan SPP Terbaru',
    'items' => $items,
];
