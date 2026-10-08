<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$academicYearId = $this->getActiveAcademicYearId();
$belum = 0;
$totalBuku = 0;

try {
    $books = DB::table('teacher_admin_books as ab')
        ->where('ab.teacher_id', $user->id)
        ->where('ab.is_active', 1)
        ->when($academicYearId, fn ($q) => $q->where('ab.academic_year_id', $academicYearId))
        ->selectRaw('ab.id,
            (SELECT COUNT(*) FROM student_class_histories h WHERE h.study_group_id = ab.study_group_id AND h.is_active = 1) as total_siswa,
            (SELECT COUNT(*) FROM admin_nilai_formatif nf WHERE nf.admin_book_id = ab.id) as total_nilai')
        ->get();

    $totalBuku = $books->count();
    $belum = $books->filter(fn ($b) => (int) $b->total_nilai < (int) $b->total_siswa)->count();
} catch (\Throwable $e) {
    Log::warning('Widget stat-tugas-belum-dinilai: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $belum,
    'label' => 'Tugas Belum Dinilai',
    'icon'  => 'ri-draft-line',
    'color' => $belum > 0 ? 'warning' : 'success',
    'sub'   => "Dari {$totalBuku} kelas-mapel aktif",
];
