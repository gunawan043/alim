<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// =============================================================================
// 4. create_tahfidz_curriculums_table.php
// Kurikulum target hafalan per jenjang per semester.
// Level: Ponpes → Paket → Kelas → Semester.
// =============================================================================
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tahfidz_curriculums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_unit_id');
            $table->uuid('school_id')->nullable();
            $table->uuid('tahfidz_package_id')->nullable();
            $table->uuid('grade_level_id')->nullable();
            $table->uuid('academic_year_id');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->integer('target_juz_start')->nullable();
            $table->integer('target_juz_end')->nullable();
            $table->integer('target_halaman')->nullable();
            $table->integer('target_hadits')->default(0);
            $table->decimal('kkm_score', 5, 2)->default(70.00);
            $table->text('description')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('work_unit_id')->references('id')->on('work_units')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('tahfidz_package_id')->references('id')->on('tahfidz_packages')->nullOnDelete();
            $table->foreign('grade_level_id')->references('id')->on('grade_levels')->nullOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['academic_year_id', 'semester', 'is_active']);
        });
    }
    public function down(): void { Schema::dropIfExists('tahfidz_curriculums'); }
};
