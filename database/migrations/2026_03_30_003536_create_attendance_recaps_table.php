<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_recaps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('study_group_id');
            $table->uuid('academic_year_id');
            $table->uuid('school_id');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->integer('total_hadir')->default(0);
            $table->integer('total_izin')->default(0);
            $table->integer('total_sakit')->default(0);
            $table->integer('total_alpa')->default(0);
            $table->integer('total_hari_efektif')->default(0);
            $table->timestamps();
 
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('study_group_id')->references('id')->on('study_groups')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
 
            $table->unique(['student_id', 'academic_year_id', 'semester'], 'unique_attendance_recap');
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('attendance_recaps');
    }
};
