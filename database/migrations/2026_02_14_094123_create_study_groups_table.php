<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignUuid('grade_level_id')->constrained('grade_levels')->cascadeOnDelete();
            
            // Wali Kelas dari tabel users
            $table->foreignUuid('homeroom_teacher_id')->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            
            $table->string('name', 50); // 7A, 7B
            $table->string('code', 20)->nullable(); // VII-A
            $table->integer('capacity')->default(32);
            $table->string('room', 50)->nullable();
            $table->enum('curriculum_type', ['merdeka', '2013', 'ktsp'])->default('merdeka');
            $table->enum('shift', ['pagi', 'siang'])->default('pagi');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->unique(['school_id', 'academic_year_id', 'name'], 'unique_study_group');
            $table->index('homeroom_teacher_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_groups');
    }
};