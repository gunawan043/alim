<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// =============================================================================
// 28. create_tahfidz_hadits_progress_table.php
// Progress hafalan Hadits Arbain per santri.
// =============================================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahfidz_hadits_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->tinyInteger('hadits_id')->unsigned();
            $table->uuid('academic_year_id');
            $table->uuid('teacher_id')->nullable();
            $table->date('setoran_date')->nullable();
            $table->enum('status', ['belum', 'sedang_hafal', 'selesai', 'perlu_ulang'])->default('belum');
            $table->decimal('nilai', 5, 2)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('hadits_id')->references('id')->on('tahfidz_hadits_master')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('teacher_id')->references('id')->on('users')->nullOnDelete();
            $table->unique(['student_id', 'hadits_id', 'academic_year_id'], 'unique_hadits_per_student');
            $table->index(
                ['student_id', 'status'],
                'idx_std_status'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_hadits_progress');
    }
};
