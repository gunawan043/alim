<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// =============================================================================
// 18. create_tahfidz_tasmian_participants_table.php
// Data peserta tasmi'an per sesi.
// Implementasi: Tabel Data Peserta Tasmi'an (NO, NAMA, KELAS, JUZ, HARI/TGL,
// WAKTU, MUSTAMI', TEMPAT).
// =============================================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahfidz_tasmian_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tasmian_session_id');
            $table->uuid('student_id');
            $table->uuid('academic_year_id');
            $table->integer('nomor_urut')->nullable();
            $table->tinyInteger('juz_tasmi')->nullable()->comment('Juz yang diujikan');
            $table->json('juz_detail')->nullable()->comment('Jika lebih dari 1 juz: [28,29,30]');
            $table->string('materi_detail', 191)->nullable()
                ->comment('Misal: Juz 30 halaman 580-604');
            $table->uuid('mustami_id')->nullable()->comment('Guru penguji / mustami\'');
            $table->time('waktu_mulai')->nullable();
            $table->time('waktu_selesai')->nullable();
            $table->string('tempat', 100)->nullable();
            $table->enum('status', ['terdaftar', 'sedang_ujian', 'selesai', 'tidak_hadir'])->default('terdaftar');
            $table->timestamps();

            $table->foreign('tasmian_session_id')->references('id')->on('tahfidz_tasmian_sessions')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('mustami_id')->references('id')->on('users')->nullOnDelete();
            $table->unique(['tasmian_session_id', 'student_id'], 'unique_participant_per_session');
            $table->index(
                ['student_id', 'academic_year_id'],
                'idx_std_acy'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_tasmian_participants');
    }
};
