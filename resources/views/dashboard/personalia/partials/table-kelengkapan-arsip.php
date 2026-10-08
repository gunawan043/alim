<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$items = [];
$total = 0;

try {
    $base = DB::table('gtk_profiles as gp')
        ->join('users as u', 'u.id', '=', 'gp.user_id')
        ->where(function ($q) {
            $q->whereNull('gp.nik')->orWhere('gp.nik', '')
                ->orWhereNull('gp.no_kk')->orWhere('gp.no_kk', '')
                ->orWhereNull('gp.npwp')->orWhere('gp.npwp', '')
                ->orWhereNull('gp.no_hp')->orWhere('gp.no_hp', '')
                ->orWhereNull('gp.tanggal_lahir');
        });

    $total = (clone $base)->count();

    $items = (clone $base)
        ->orderBy('u.name')
        ->limit(10)
        ->get(['u.name', 'gp.nik', 'gp.no_kk', 'gp.npwp', 'gp.no_hp', 'gp.tanggal_lahir'])
        ->map(function ($row) {
            $missing = [];
            if (empty($row->nik)) {
                $missing[] = 'NIK';
            }
            if (empty($row->no_kk)) {
                $missing[] = 'No. KK';
            }
            if (empty($row->npwp)) {
                $missing[] = 'NPWP';
            }
            if (empty($row->no_hp)) {
                $missing[] = 'No. HP';
            }
            if (empty($row->tanggal_lahir)) {
                $missing[] = 'Tgl lahir';
            }

            return (object) ['name' => $row->name, 'missing' => implode(', ', $missing)];
        })
        ->all();
} catch (\Throwable $e) {
    Log::warning('Widget table-kelengkapan-arsip: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Arsip GTK Belum Lengkap',
    'total' => $total,
    'items' => $items,
];
