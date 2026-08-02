<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_class_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('study_group_id')->constrained('study_groups')->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();

            $table->integer('attendance_number')->nullable(); // Nomor absen
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->date('join_date')->nullable();
            $table->date('leave_date')->nullable();

            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id'], 'unique_student_class');
            $table->index('study_group_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_class_histories');
    }
};
