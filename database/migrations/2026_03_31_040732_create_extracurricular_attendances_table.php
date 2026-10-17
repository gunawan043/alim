<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extracurricular_attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('extracurricular_id');
            $table->uuid('student_id');
            $table->uuid('school_id');
            $table->uuid('academic_year_id');
            $table->date('attendance_date');
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa']);
            $table->text('notes')->nullable();
            $table->uuid('recorded_by');
            $table->timestamps();

            $table->foreign('extracurricular_id')->references('id')->on('extracurriculars')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users');

            $table->unique(
                ['extracurricular_id', 'student_id', 'attendance_date'],
                'unique_extracurricular_attendance'
            );
            $table->index(
                ['extracurricular_id', 'attendance_date'],
                'idx_excul_att_date'
            );

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extracurricular_attendances');
    }
};
