<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// =============================================================================
// 16. create_tahfidz_evaluations_table.php
// Evaluasi berkala: bulanan, tengah semester, akhir semester, kenaikan juz.
// =============================================================================
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tahfidz_evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('evaluator_id');
            $table->uuid('tahfidz_group_id')->nullable();
            $table->uuid('academic_year_id');
            $table->date('evaluation_date');
            $table->enum('evaluation_type', ['bulanan', 'tengah_semester', 'akhir_semester', 'kenaikan_juz']);
            $table->json('juz_diuji')->nullable()->comment('Array juz yang diuji: [29, 30]');
            $table->decimal('halaman_diuji', 5, 1)->nullable();
            $table->decimal('nilai_tahfizh', 5, 2)->nullable();
            $table->decimal('nilai_tajwid', 5, 2)->nullable();
            $table->decimal('nilai_fashohah', 5, 2)->nullable();
            $table->decimal('nilai_keseluruhan', 5, 2)->nullable();
            $table->enum('predikat', ['mumtaz', 'jayyid_jiddan', 'jayyid', 'maqbul', 'rasib'])->nullable();
            $table->text('rekomendasi')->nullable();
            $table->enum('status', ['lulus', 'tidak_lulus', 'perlu_perbaikan'])->default('lulus');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('evaluator_id')->references('id')->on('users');
            $table->foreign('tahfidz_group_id')->references('id')->on('tahfidz_groups')->nullOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->index(['student_id', 'evaluation_type', 'evaluation_date'],
                'idx_std_eval_type_date');
        });
    }
    public function down(): void { Schema::dropIfExists('tahfidz_evaluations'); }
};
