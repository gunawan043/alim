<?php

use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$fields = Student::PROFILE_FIELDS;
$items = [];
$total = 0;

try {
    $base = DB::table('students')
        ->whereNull('deleted_at')
        ->where('status', 'active')
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
        ->where(function ($q) use ($fields) {
            foreach (array_keys($fields) as $column) {
                $q->orWhereNull($column)->orWhere($column, '');
            }
        });

    $total = (clone $base)->count();

    $items = (clone $base)
        ->orderBy('name')
        ->limit(10)
        ->get(array_merge(['id', 'name'], array_keys($fields)))
        ->map(function ($s) use ($fields) {
            $missing = [];
            foreach ($fields as $column => $label) {
                if (empty($s->{$column})) {
                    $missing[] = $label;
                }
            }

            return [
                'name'    => $s->name,
                'missing' => implode(', ', $missing),
                // Tautan langsung ke form pelengkapan data santri.
                'url'     => route('user.students.edit', [
                    'userId'     => $user->id,
                    'santriUuid' => $s->id,
                ]),
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
