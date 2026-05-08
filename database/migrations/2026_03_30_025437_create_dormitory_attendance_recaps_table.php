<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dormitory_attendance_recaps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('room_id');
            $table->uuid('dormitory_id');
            $table->uuid('academic_year_id');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->tinyInteger('recap_month');
            $table->year('recap_year');
            $table->integer('total_hadir')->default(0);
            $table->integer('total_izin')->default(0);
            $table->integer('total_sakit')->default(0);
            $table->integer('total_alpa')->default(0);
            $table->integer('total_pulang')->default(0);
            $table->timestamps();
 
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('room_id')->references('id')->on('dormitory_rooms')->cascadeOnDelete();
            $table->foreign('dormitory_id')->references('id')->on('dormitories')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
 
            $table->unique(
                ['student_id', 'academic_year_id', 'recap_month', 'recap_year'],
                'unique_dormitory_recap_per_month'
            );
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('dormitory_attendance_recaps');
    }
};