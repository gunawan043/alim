<?php

use Illuminate\Support\Facades\DB;

$items = $this->scopeAsramaQuery(
    DB::table('dormitory_posts')
        ->where('is_active', 1)
        ->whereNull('deleted_at'),
    $user,
    'dormitory_id',
    'dormitory_id'
)
    ->orderByDesc('is_pinned')
    ->orderByDesc('created_at')
    ->limit(10)
    ->get(['title', 'category', 'needs_response', 'visibility', 'created_at'])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Pengumuman Asrama',
    'items' => $items,
];
