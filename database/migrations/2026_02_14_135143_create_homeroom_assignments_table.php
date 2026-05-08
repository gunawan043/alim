<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homeroom_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Relasi ke SK
            $table->foreignUuid('decree_id')
                  ->constrained('institution_decrees')
                  ->cascadeOnDelete();
            
            // Guru sebagai wali kelas
            $table->foreignUuid('teacher_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            
            $table->foreignUuid('study_group_id')
                  ->constrained('study_groups')
                  ->cascadeOnDelete();
            
            $table->foreignUuid('school_id')
                  ->constrained('schools')
                  ->cascadeOnDelete();
            
            $table->foreignUuid('academic_year_id')
                  ->constrained('academic_years')
                  ->cascadeOnDelete();
            
            // Masa tugas
            $table->date('start_date');
            $table->date('end_date')->nullable();
            
            // Status
            $table->enum('status', ['active', 'inactive', 'ended'])->default('active');
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['decree_id', 'study_group_id'], 'unique_homeroom_assign');
            $table->index('teacher_id');
            $table->index('study_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homeroom_assignments');
    }
};