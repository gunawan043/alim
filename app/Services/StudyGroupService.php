<?php

namespace App\Services;

use App\Models\StudyGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Operasi rombel (StudyGroup) yang aman terhadap foreign key.
 *
 * Mayoritas FK sudah cascade (teaching_assignments, study_group_subjects,
 * teacher_admin_books, attendance, dst.), tetapi beberapa bersifat RESTRICTIF
 * (jadwal_kbms, riwayat kenaikan kelas, agenda) sehingga harus dibersihkan dulu.
 */
class StudyGroupService
{
    public function deleteSafely(StudyGroup $studyGroup): void
    {
        DB::transaction(function () use ($studyGroup) {
            // Jadwal pelajaran — FK restriktif.
            DB::table('jadwal_kbms')->where('study_group_id', $studyGroup->id)->delete();

            // Riwayat kenaikan kelas (from_study_group_id restriktif).
            if (Schema::hasTable('student_grade_promotions')) {
                DB::table('student_grade_promotions')->where('from_study_group_id', $studyGroup->id)->delete();
            }

            // Presensi agenda (jika restriktif — aman dihapus bersama rombel).
            if (Schema::hasTable('agenda_attendees')) {
                DB::table('agenda_attendees')->where('study_group_id', $studyGroup->id)->delete();
            }

            // Sisanya cascade: assignments, mapel-rombel, buku nilai, absensi, dsb.
            $studyGroup->delete();
        });
    }
}
