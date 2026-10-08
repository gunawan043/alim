<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Widget Wali Kelas: jumlah santri di kelas (rombel) yang diampu user.
 * Sumber rombel canonical: homeroom_assignments / homeroom_teachers
 * (lihat DashboardController::getHomeroomStudyGroup).
 */
$rombel = $this->getHomeroomStudyGroup($user);

$total = 0;

if ($rombel) {
    try {
        $total = DB::table('student_class_histories')
            ->join('students', 'students.id', '=', 'student_class_histories.student_id')
            ->where('student_class_histories.study_group_id', $rombel->id)
            ->where('student_class_histories.is_active', 1)
            ->where('students.status', 'active')
            ->count();

        if ($total === 0) {
            $total = DB::table('student_class_histories')
                ->where('study_group_id', $rombel->id)
                ->where('is_active', 1)
                ->count();
        }
    } catch (\Throwable $e) {
        Log::warning('Widget stat-santri-kelas-saya (santri): ' . $e->getMessage());
    }
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $rombel ? $total : null,
    'label' => 'Santri Kelas Saya',
    'icon'  => 'ri-group-line',
    'color' => 'primary',
    'sub'   => $rombel
        ? ('Kelas ' . $rombel->name . ($rombel->room ? ' · Ruang ' . $rombel->room : ''))
        : 'Anda belum ditetapkan sebagai wali kelas',
];
