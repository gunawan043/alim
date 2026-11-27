<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pasangan mapel–kelas untuk tugas tambahan Guru Pendamping.
 *
 * Satu guru boleh mendampingi mapel tertentu di kelas tertentu saja
 * (Matematika 7A tapi IPA 7B), jadi relasi disimpan per pasangan, bukan
 * dua daftar terpisah yang menyiratkan semua kombinasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('other_teacher_task_subjects')) {
            return;
        }

        Schema::create('other_teacher_task_subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('other_teacher_task_id');
            $table->uuid('subject_id');
            $table->uuid('study_group_id');
            $table->timestamps();

            $table->unique(['other_teacher_task_id', 'subject_id', 'study_group_id'], 'otts_unique');
            $table->index(['subject_id']);
            $table->index(['study_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('other_teacher_task_subjects');
    }
};
