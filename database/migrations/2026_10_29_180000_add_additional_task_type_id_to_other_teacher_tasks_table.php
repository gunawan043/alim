<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tugas tambahan sebelumnya hanya menyimpan teks bebas (task_name/task_code),
     * sehingga nama tugas bisa menyimpang dari master additional_task_types.
     * Kolom ini mengikat setiap baris ke master jenis tugas.
     */
    public function up(): void
    {
        if (Schema::hasColumn('other_teacher_tasks', 'additional_task_type_id')) {
            return;
        }

        Schema::table('other_teacher_tasks', function (Blueprint $table) {
            $table->uuid('additional_task_type_id')->nullable()->after('academic_year_id');
            $table->index('additional_task_type_id', 'ott_task_type_idx');
            $table->foreign('additional_task_type_id', 'ott_task_type_fk')
                ->references('id')
                ->on('additional_task_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('other_teacher_tasks', function (Blueprint $table) {
            $table->dropForeign('ott_task_type_fk');
            $table->dropIndex('ott_task_type_idx');
            $table->dropColumn('additional_task_type_id');
        });
    }
};
