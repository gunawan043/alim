<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$items = [];

try {
    $items = DB::table('agendas')
        ->whereNull('deleted_at')
        ->where('start_datetime', '>=', now())
        ->where('start_datetime', '<=', now()->addDays(14))
        ->where(function ($q) {
            $q->whereNull('status')->orWhereNotIn('status', ['cancelled', 'canceled', 'dibatalkan']);
        })
        ->when($schoolId, function ($q) use ($schoolId) {
            $q->where(function ($sub) use ($schoolId) {
                $sub->where('school_id', $schoolId)->orWhereNull('school_id');
            });
        })
        ->orderBy('start_datetime')
        ->limit(10)
        ->get(['title', 'start_datetime', 'end_datetime', 'location_name', 'is_mandatory', 'scope'])
        ->all();
} catch (\Throwable $e) {
    Log::warning('Widget table-agenda-mendatang: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Agenda 14 Hari ke Depan',
    'items' => $items,
];
