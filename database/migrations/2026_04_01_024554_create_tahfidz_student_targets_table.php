<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// =============================================================================
// 11. create_tahfidz_student_targets_table.php
// Target hafalan per santri per bulan, mengacu ke muqorrar.
// =============================================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahfidz_student_targets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('tahfidz_group_id');
            $table->uuid('academic_year_id');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->tinyInteger('target_bulan')->nullable()->comment('Bulan 1-12');
            $table->tinyInteger('juz_start')->nullable();
            $table->tinyInteger('juz_end')->nullable();
            $table->smallInteger('surah_start_id')->unsigned()->nullable();
            $table->integer('ayat_start')->nullable();
            $table->smallInteger('surah_end_id')->unsigned()->nullable();
            $table->integer('ayat_end')->nullable();
            $table->decimal('target_halaman', 5, 1)->nullable();
            $table->integer('target_hadits')->default(0);
            $table->uuid('muqorrar_id')->nullable()->comment('Muqorrar acuan');
            $table->uuid('assigned_by');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('tahfidz_group_id')->references('id')->on('tahfidz_groups')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('muqorrar_id')->references('id')->on('tahfidz_muqorrars')->nullOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users');
            $table->index(
                ['student_id', 'academic_year_id', 'semester'],
                'idx_std_acy_sem'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_student_targets');
    }
};
