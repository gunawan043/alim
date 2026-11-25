<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rekap presensi S/I/A yang dipakai leger STS & SAS.
 *
 * Nilai di tabel ini adalah angka final yang tercetak di leger dan rapor.
 * Bila belum pernah diisi, leger menurunkan angkanya dari admin_presensi_harian
 * (hadir + terlambat = S, izin + sakit = I, alpa = A).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_leger_presensi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('study_group_id')->constrained('study_groups')->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('semester', 10);
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->unsignedSmallInteger('s')->nullable();
            $table->unsignedSmallInteger('i')->nullable();
            $table->unsignedSmallInteger('a')->nullable();
            $table->timestamps();

            $table->unique(
                ['study_group_id', 'academic_year_id', 'semester', 'student_id'],
                'leger_presensi_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_leger_presensi');
    }
};
