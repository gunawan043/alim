<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// =============================================================================
// 10. create_tahfidz_group_attendances_table.php
// Absensi kehadiran per sesi halaqah.
// =============================================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahfidz_group_attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tahfidz_group_id');
            $table->uuid('student_id');
            $table->uuid('academic_year_id');
            $table->date('attendance_date');
            $table->enum('session_type', ['setoran', 'murajaah', 'tasmi', 'evaluasi', 'tikror'])->default('setoran');
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa'])->default('hadir');
            $table->text('notes')->nullable();
            $table->uuid('recorded_by');
            $table->timestamps();

            $table->foreign('tahfidz_group_id')->references('id')->on('tahfidz_groups')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users');
            $table->unique(['tahfidz_group_id', 'student_id', 'attendance_date', 'session_type'], 'unique_attendance_per_session');
            $table->index(['student_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_group_attendances');
    }
};
