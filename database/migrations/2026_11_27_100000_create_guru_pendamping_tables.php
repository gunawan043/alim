<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guru Pendamping: tugas tambahan yang mengikat MAPEL dan KELAS.
 *
 * Mapel disimpan di tingkat TIPE tugas (sekali set untuk semua guru
 * pendamping: mis. Matematika + IPA). Kelas disimpan per baris tugas
 * GTK, karena dua guru pendamping bisa memegang kelas berbeda.
 *
 * JP tidak disimpan di sini — nilainya dibaca dari Peta Kurikulum
 * (grade_level_subjects.allocation_hours) lalu dijumlahkan ke
 * hours_per_week tugas tambahan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('additional_task_subjects')) {
            Schema::create('additional_task_subjects', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('additional_task_type_id');
                $table->uuid('subject_id');
                $table->unsignedSmallInteger('urutan')->default(0);
                $table->timestamps();

                $table->unique(['additional_task_type_id', 'subject_id'], 'task_type_subject_unique');
                $table->foreign('additional_task_type_id', 'task_type_subject_type_fk')
                    ->references('id')->on('additional_task_types')->cascadeOnDelete();
                $table->foreign('subject_id', 'task_type_subject_subject_fk')
                    ->references('id')->on('subjects')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('gtk_additional_task_study_groups')) {
            Schema::create('gtk_additional_task_study_groups', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('gtk_additional_task_id');
                $table->uuid('study_group_id');
                $table->timestamps();

                $table->unique(['gtk_additional_task_id', 'study_group_id'], 'task_study_group_unique');
                $table->foreign('gtk_additional_task_id', 'task_study_group_task_fk')
                    ->references('id')->on('gtk_additional_tasks')->cascadeOnDelete();
                $table->foreign('study_group_id', 'task_study_group_group_fk')
                    ->references('id')->on('study_groups')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gtk_additional_task_study_groups');
        Schema::dropIfExists('additional_task_subjects');
    }
};
