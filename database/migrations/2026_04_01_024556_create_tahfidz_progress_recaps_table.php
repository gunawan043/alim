<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// =============================================================================
// 15. create_tahfidz_progress_recaps_table.php
// Rekap total hafalan per santri per semester. Di-generate otomatis.
// =============================================================================
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tahfidz_progress_recaps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('tahfidz_group_id')->nullable();
            $table->uuid('academic_year_id');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->decimal('total_juz_ziyadah', 4, 1)->default(0);
            $table->decimal('total_halaman_ziyadah', 6, 1)->default(0);
            $table->decimal('total_juz_murajaah', 4, 1)->default(0);
            $table->decimal('total_halaman_murajaah', 6, 1)->default(0);
            $table->integer('total_setoran')->default(0);
            $table->integer('total_hari_setoran')->default(0);
            $table->decimal('rata_rata_nilai', 5, 2)->nullable();
            $table->decimal('pencapaian_target_persen', 5, 2)->nullable()
                  ->comment('% capaian dari target muqorrar');
            $table->tinyInteger('last_position_juz')->nullable();
            $table->smallInteger('last_position_surah_id')->unsigned()->nullable();
            $table->integer('last_position_ayat')->nullable();
            $table->decimal('last_position_halaman', 5, 1)->nullable();
            $table->integer('total_juz_completed')->default(0);
            $table->integer('hadits_count')->default(0);
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('tahfidz_group_id')->references('id')->on('tahfidz_groups')->nullOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('last_position_surah_id')->references('id')->on('tahfidz_surah_master')->nullOnDelete();
            $table->unique(['student_id', 'academic_year_id', 'semester'], 'unique_recap_per_semester');
        });
    }
    public function down(): void { Schema::dropIfExists('tahfidz_progress_recaps'); }
};
