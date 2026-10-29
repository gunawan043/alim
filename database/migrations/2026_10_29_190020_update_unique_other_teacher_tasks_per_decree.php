<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tugas tambahan kini milik SK tertentu. Unique lama tidak menyertakan
     * decree_id sehingga dua SK pada tahun ajaran yang sama tidak boleh
     * memiliki tugas dengan kode yang sama untuk guru yang sama.
     */
    public function up(): void
    {
        if (! Schema::hasTable('other_teacher_tasks') || ! Schema::hasColumn('other_teacher_tasks', 'decree_id')) {
            return;
        }

        $indexes = collect(Schema::getIndexes('other_teacher_tasks'))->pluck('name');

        // MySQL membutuhkan index untuk FK teacher_id. Unique lama adalah
        // satu-satunya index yang diawali teacher_id, jadi tambahkan index
        // pengganti sebelum unique tersebut dilepas.
        if (! $indexes->contains('ott_teacher_idx')) {
            Schema::table('other_teacher_tasks', function (Blueprint $table) {
                $table->index('teacher_id', 'ott_teacher_idx');
            });
        }

        if ($indexes->contains('unique_teacher_task')) {
            Schema::table('other_teacher_tasks', function (Blueprint $table) {
                $table->dropUnique('unique_teacher_task');
            });
        }

        $indexes = collect(Schema::getIndexes('other_teacher_tasks'))->pluck('name');

        if (! $indexes->contains('unique_teacher_task_per_decree')) {
            Schema::table('other_teacher_tasks', function (Blueprint $table) {
                $table->unique(
                    ['decree_id', 'teacher_id', 'academic_year_id', 'task_code', 'study_group_id'],
                    'unique_teacher_task_per_decree'
                );
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('other_teacher_tasks')) {
            return;
        }

        $indexes = collect(Schema::getIndexes('other_teacher_tasks'))->pluck('name');

        if ($indexes->contains('unique_teacher_task_per_decree')) {
            Schema::table('other_teacher_tasks', function (Blueprint $table) {
                $table->dropUnique('unique_teacher_task_per_decree');
            });
        }

        $indexes = collect(Schema::getIndexes('other_teacher_tasks'))->pluck('name');

        if (! $indexes->contains('unique_teacher_task')) {
            Schema::table('other_teacher_tasks', function (Blueprint $table) {
                $table->unique(
                    ['teacher_id', 'academic_year_id', 'task_code', 'study_group_id'],
                    'unique_teacher_task'
                );
            });
        }
    }
};
