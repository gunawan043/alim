<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignUuid('study_group_id')->constrained('study_groups')->cascadeOnDelete();
            $table->foreignUuid('subject_id')->constrained('subjects')->cascadeOnDelete();
            
            // Guru yang mengajar (dari tabel users)
            $table->foreignUuid('teacher_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            
            $table->boolean('is_primary')->default(true); // Guru utama/pendamping
            $table->enum('status', ['active', 'inactive'])->default('active');
            
            // Data SK mengajar
            $table->string('decree_number', 100)->nullable();
            $table->date('decree_date')->nullable();
            $table->integer('weekly_hours')->default(0); // Jumlah jam mengajar per minggu
            
            $table->timestamps();
            
            $table->unique(['academic_year_id', 'study_group_id', 'subject_id', 'teacher_id'], 'unique_teaching');
            $table->index('teacher_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachings');
    }
};