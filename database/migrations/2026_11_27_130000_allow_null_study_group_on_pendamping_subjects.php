<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mapel Guru Pendamping boleh tersimpan tanpa kelas: kelas dicentang
 * belakangan pada kolom rombel di matriks SK. Barisnya tetap satu, dengan
 * study_group_id NULL, lalu diganti baris per kelas saat kelas dipilih.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('other_teacher_task_subjects', function (Blueprint $table) {
            $table->uuid('study_group_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('other_teacher_task_subjects', function (Blueprint $table) {
            $table->uuid('study_group_id')->nullable(false)->change();
        });
    }
};
