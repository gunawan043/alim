<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Peserta per ujian LJK + pemetaan aktual nomor soal → butir paket.
 *
 * `mapping` menyimpan urutan dan opsi yang benar-benar digunakan peserta
 * (penting ketika paket mengacak soal/opsi). Isi soal dan kunci TIDAK
 * disalin — hanya referensi item_id/soal_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ljk_participants')) {
            return;
        }

        Schema::create('ljk_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ljk_exam_id');
            $table->uuid('student_id');
            $table->uuid('study_group_id')->nullable();
            $table->string('nomor_peserta', 16);
            $table->json('mapping')->nullable();
            $table->string('status', 24)->default('terdaftar');
            $table->timestamp('sheet_generated_at')->nullable();
            $table->unsignedSmallInteger('total_point')->nullable();
            $table->decimal('nilai_akhir', 6, 2)->nullable();
            $table->timestamps();

            $table->foreign('ljk_exam_id', 'ljk_participants_exam_fk')
                ->references('id')->on('ljk_exams')->cascadeOnDelete();
            $table->foreign('student_id', 'ljk_participants_student_fk')
                ->references('id')->on('students')->restrictOnDelete();

            $table->unique(['ljk_exam_id', 'student_id'], 'ljk_participants_exam_student_unique');
            $table->unique(['ljk_exam_id', 'nomor_peserta'], 'ljk_participants_exam_nomor_unique');
            $table->index('student_id', 'ljk_participants_student_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ljk_participants');
    }
};
