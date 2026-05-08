<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homeroom_teachers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Relasi ke users (GTK yang menjadi wali kelas)
            $table->foreignUuid('teacher_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            
            $table->foreignUuid('study_group_id')
                  ->constrained('study_groups')
                  ->cascadeOnDelete();
            
            $table->foreignUuid('academic_year_id')
                  ->constrained('academic_years')
                  ->cascadeOnDelete();
            
            $table->foreignUuid('school_id')
                  ->constrained('schools')
                  ->cascadeOnDelete();
            
            // Data SK dan masa tugas
            $table->string('decree_number', 100)->nullable();
            $table->date('decree_date')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Satu guru tidak bisa jadi wali kelas di rombel yang sama dalam 1 TA
            $table->unique(['academic_year_id', 'study_group_id'], 'unique_homeroom');
            
            $table->index('teacher_id');
            $table->index('school_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homeroom_teachers');
    }
};