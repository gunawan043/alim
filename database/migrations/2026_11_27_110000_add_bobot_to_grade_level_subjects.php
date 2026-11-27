<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bobot rapor SAS kini disimpan per TINGKAT, bukan per rombel.
 *
 * Satu tingkat = satu template rapor: urutan, asal mapel, KKTP, dan bobot
 * tidak boleh berbeda antar rombel. Kolom `study_group_subjects.bobot` yang
 * lama hanya dipakai sebagai sumber awal (backfill) bila nilainya seragam di
 * seluruh rombel pada tingkat itu.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('grade_level_subjects') || Schema::hasColumn('grade_level_subjects', 'bobot')) {
            return;
        }

        Schema::table('grade_level_subjects', function (Blueprint $table) {
            $table->decimal('bobot', 6, 2)->nullable()->after('subject_source')
                ->comment('Bobot rapor SAS per tingkat; null = JP ÷ 2');
        });

        $this->backfillFromRombel();
    }

    public function down(): void
    {
        if (! Schema::hasTable('grade_level_subjects') || ! Schema::hasColumn('grade_level_subjects', 'bobot')) {
            return;
        }

        Schema::table('grade_level_subjects', function (Blueprint $table) {
            $table->dropColumn('bobot');
        });
    }

    /**
     * Salin bobot rombel ke tingkat hanya bila seragam: satu nilai untuk semua
     * rombel aktif pada (tingkat × TA × mapel). Kasus lain dibiarkan kosong
     * agar kembali ke JP ÷ 2, bukan memilih salah satu rombel secara acak.
     */
    private function backfillFromRombel(): void
    {
        if (! Schema::hasTable('study_group_subjects') || ! Schema::hasColumn('study_group_subjects', 'bobot')) {
            return;
        }

        $rows = DB::table('study_group_subjects')
            ->join('study_groups', 'study_groups.id', '=', 'study_group_subjects.study_group_id')
            ->whereNotNull('study_group_subjects.bobot')
            ->get([
                'study_groups.grade_level_id',
                'study_group_subjects.academic_year_id',
                'study_group_subjects.subject_id',
                'study_group_subjects.bobot',
            ]);

        $grouped = $rows->groupBy(fn ($row) => implode('|', [
            $row->grade_level_id,
            $row->academic_year_id,
            $row->subject_id,
        ]));

        foreach ($grouped as $items) {
            $distinct = $items->pluck('bobot')->unique(fn ($v) => (string) (float) $v);

            if ($distinct->count() !== 1) {
                continue;
            }

            $first = $items->first();

            DB::table('grade_level_subjects')
                ->where('grade_level_id', $first->grade_level_id)
                ->where('academic_year_id', $first->academic_year_id)
                ->where('subject_id', $first->subject_id)
                ->update(['bobot' => $distinct->first()]);
        }
    }
};
