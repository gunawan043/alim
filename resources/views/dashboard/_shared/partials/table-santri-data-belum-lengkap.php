<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$items = [];
$total = 0;

try {
    $base = DB::table('students')
        ->where('status', 'active')
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
        ->where(function ($q) {
            $q->whereNull('nis')->orWhere('nis', '')
                ->orWhereNull('nik')->orWhere('nik', '')
                ->orWhereNull('birth_date')
                ->orWhereNull('father_name')
                ->orWhereNull('mother_name');
        });

    $total = (clone $base)->count();

    $items = (clone $base)
        ->orderBy('name')
        ->limit(10)
        ->get(['name', 'nis', 'nik', 'birth_date', 'father_name', 'mother_name'])
        ->map(function ($s) {
            $missing = [];
            if (empty($s->nis)) {
                $missing[] = 'NIS';
            }
            if (empty($s->nik)) {
                $missing[] = 'NIK';
            }
            if (empty($s->birth_date)) {
                $missing[] = 'Tgl lahir';
            }
            if (empty($s->father_name)) {
                $missing[] = 'Ayah';
            }
            if (empty($s->mother_name)) {
                $missing[] = 'Ibu';
            }

            return [
                'name'    => $s->name,
                'missing' => implode(', ', $missing),
            ];
        })
        ->all();
} catch (\Throwable $e) {
    Log::warning('Widget table-santri-data-belum-lengkap: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Data Santri Belum Lengkap',
    'total' => $total,
    'items' => $items,
];
