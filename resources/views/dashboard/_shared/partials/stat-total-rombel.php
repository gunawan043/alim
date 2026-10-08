<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);
$academicYearId = $this->getActiveAcademicYearId();

$total = DB::table('study_groups')
    ->where('is_active', 1)
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
    ->count();

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $total,
    'label' => 'Rombel Aktif',
    'icon'  => 'ri-building-2-line',
    'color' => 'info',
    'sub'   => 'Tahun ajaran aktif',
];
