<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_kktp', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('subject_id');
            $table->uuid('school_id');
            $table->uuid('grade_level_id');
            $table->uuid('academic_year_id');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->decimal('kktp_score', 5, 2)->nullable()->comment('Kriteria Ketercapaian Tujuan Pembelajaran');
            $table->decimal('kkm_score', 5, 2)->nullable()->comment('Kriteria Ketuntasan Minimal');
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('grade_level_id')->references('id')->on('grade_levels')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users');

            $table->unique(['subject_id', 'grade_level_id', 'academic_year_id', 'semester'], 'unique_kktp_per_subject_class');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_kktp');
    }
};
