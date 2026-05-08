<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// =============================================================================
// 19. create_tahfidz_tasmian_scores_table.php
// Nilai tasmi'an per peserta.
// Komponen: Tahfizh (bobot 40) + Tajwid (bobot 30) + Fashohah (bobot 30) = 100.
// Sesuai Instrumen Penilaian Tasmi'an (Gambar 3).
// =============================================================================
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tahfidz_tasmian_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('participant_id')->unique()->comment('1:1 dengan tasmian_participants');
            $table->uuid('student_id');
            $table->uuid('tasmian_session_id');
            $table->uuid('evaluator_id');

            // --- TAHFIZH (bobot 40) ---
            $table->decimal('tahfizh_score_raw', 5, 2)->nullable()
                  ->comment('Skor mentah Tahfizh sebelum dikali bobot');
            $table->decimal('tahfizh_nilai', 5, 2)->nullable()
                  ->comment('Nilai setelah bobot 40: (score_raw/100)*40');

            // --- TAJWID (bobot 30) ---
            $table->decimal('tajwid_score_raw', 5, 2)->nullable();
            $table->decimal('tajwid_nilai', 5, 2)->nullable();

            // --- FASHOHAH (bobot 30) ---
            $table->decimal('fashohah_score_raw', 5, 2)->nullable();
            $table->decimal('fashohah_nilai', 5, 2)->nullable();

            $table->decimal('nilai_akhir', 5, 2)->nullable()
                  ->comment('= tahfizh_nilai + tajwid_nilai + fashohah_nilai');
            $table->enum('predikat', ['mumtaz', 'jayyid_jiddan', 'jayyid', 'maqbul', 'rasib'])->nullable();
            $table->text('catatan_penilai')->nullable();
            $table->timestamps();

            $table->foreign('participant_id')->references('id')->on('tahfidz_tasmian_participants')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('tasmian_session_id')->references('id')->on('tahfidz_tasmian_sessions')->cascadeOnDelete();
            $table->foreign('evaluator_id')->references('id')->on('users');
            $table->index(
                ['tasmian_session_id', 'nilai_akhir'],
                'idx_tasm_sess_score'
            );
        });
    }
    public function down(): void { Schema::dropIfExists('tahfidz_tasmian_scores'); }
};
