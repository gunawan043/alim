<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coordinator_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Relasi ke SK
            $table->foreignUuid('decree_id')
                  ->constrained('institution_decrees')
                  ->cascadeOnDelete();
            
            // Guru sebagai koordinator
            $table->foreignUuid('teacher_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            
            // Area koordinasi (bisa unit, tingkat, atau bidang tertentu)
            $table->string('coordination_area');
            $table->string('coordination_type')->nullable(); // e.g., 'unit', 'grade', 'subject', 'activity'
            
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
            $table->text('description')->nullable();
            $table->text('responsibilities')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indeks
            $table->index('teacher_id');
            $table->index('coordination_area');
            $table->unique(['decree_id', 'teacher_id', 'coordination_area'], 'unique_coordinator_assign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coordinator_assignments');
    }
};