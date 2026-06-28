<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('paket_soal_id', 36);
            $table->char('student_id', 36);
            $table->char('admin_book_id', 36)->nullable();
            $table->char('study_group_id', 36)->nullable();
            $table->char('academic_year_id', 36);
            $table->enum('semester', ['ganjil', 'genap']);
            $table->enum('jenis_ujian', ['sts', 'sas', 'ulangan_harian', 'try_out', 'latihan']);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->enum('status', ['in_progress', 'submitted', 'graded', 'voided'])->default('in_progress');
            $table->decimal('skor_total', 6, 2)->nullable();
            $table->decimal('skor_otomatis', 6, 2)->nullable();
            $table->decimal('skor_manual', 6, 2)->nullable();
            $table->char('penilai_manual_id', 36)->nullable();
            $table->timestamp('scored_at')->nullable();
            $table->boolean('is_final')->default(false);
            $table->boolean('flagged_suspicious')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('paket_soal_id')->references('id')->on('paket_soal')->onDelete('restrict');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('restrict');
            $table->foreign('admin_book_id')->references('id')->on('teacher_admin_books')->onDelete('set null');
            $table->foreign('study_group_id')->references('id')->on('study_groups')->onDelete('set null');
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('restrict');
            $table->foreign('penilai_manual_id')->references('id')->on('users')->onDelete('set null');

            $table->unique(['paket_soal_id', 'student_id', 'jenis_ujian'], 'exam_attempts_unique_paket_student_jenis');
            $table->index(['student_id', 'academic_year_id', 'semester', 'jenis_ujian'], 'exam_attempts_idx_student_year_jenis');
            $table->index(['paket_soal_id', 'status'], 'exam_attempts_idx_paket_status');
            $table->index('status', 'exam_attempts_idx_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
