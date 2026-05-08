<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_presensi_harian', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('study_group_id')->constrained('study_groups')->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa'])->default('hadir');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['study_group_id', 'academic_year_id', 'semester', 'attendance_date', 'student_id'], 'presensi_harian_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_presensi_harian');
    }
};