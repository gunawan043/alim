<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$bankCount = 0;
$soalCount = 0;

try {
    $query = DB::table('bank_soal')
        ->whereNull('deleted_at')
        ->where(function ($q) use ($user) {
            $q->where('owner_user_id', $user->id)
                ->orWhere('created_by', $user->id);
        });

    $bankCount = (clone $query)->count();
    $soalCount = (int) (clone $query)->sum('total_soal');
} catch (\Throwable $e) {
    Log::warning('Widget stat-bank-soal-saya: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $bankCount,
    'label' => 'Bank Soal Saya',
    'icon'  => 'ri-file-list-3-line',
    'color' => 'info',
    'sub'   => number_format($soalCount, 0, ',', '.') . ' total soal',
];
