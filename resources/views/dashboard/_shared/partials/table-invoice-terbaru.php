<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$items = [];

try {
    $items = DB::table('invoice_approvals as i')
        ->leftJoin('vendors as v', 'v.id', '=', 'i.vendor_id')
        ->orderByDesc('i.invoice_date')
        ->orderByDesc('i.created_at')
        ->limit(10)
        ->get([
            'i.approval_number', 'i.invoice_number', 'v.name as vendor',
            'i.total_amount', 'i.due_date', 'i.status',
        ])
        ->all();
} catch (\Throwable $e) {
    Log::warning('Widget table-invoice-terbaru: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Invoice Terbaru',
    'items' => $items,
];
