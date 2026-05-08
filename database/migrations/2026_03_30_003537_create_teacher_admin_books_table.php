<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teacher_admin_books', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('teacher_id');
            $table->uuid('subject_id');
            $table->uuid('study_group_id');
            $table->uuid('school_id');
            $table->uuid('academic_year_id');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->uuid('teaching_id')->nullable()->comment('Referensi ke tabel teachings');
            $table->uuid('kktp_id')->nullable()->comment('Referensi ke subject_kktp');
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
 
            $table->foreign('teacher_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('study_group_id')->references('id')->on('study_groups')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('teaching_id')->references('id')->on('teachings')->nullOnDelete();
            $table->foreign('kktp_id')->references('id')->on('subject_kktp')->nullOnDelete();
 
            $table->unique(
                ['teacher_id', 'subject_id', 'study_group_id', 'academic_year_id', 'semester'],
                'unique_admin_book_per_teacher'
            );
 
            $table->index(['teacher_id', 'academic_year_id', 'semester']);
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('teacher_admin_books');
    }
};
 
