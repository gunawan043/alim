<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_id');
            $table->uuid('academic_year_id');
            $table->uuid('study_group_id');
            $table->uuid('student_id');
            $table->uuid('recorded_by')->comment('Wali kelas yang mencatat');
            $table->date('attendance_date');
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa']);
            $table->time('arrival_time')->nullable();
            $table->time('leave_time')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
 
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('study_group_id')->references('id')->on('study_groups')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users');
            $table->foreign('verified_by')->references('id')->on('users');
 
            // Index untuk filter harian per kelas
            $table->index(['study_group_id', 'attendance_date']);
            $table->index(['student_id', 'academic_year_id']);
            $table->unique(['student_id', 'study_group_id', 'attendance_date'], 'unique_student_attendance_per_day');
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};