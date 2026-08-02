<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// =============================================================================
// 7. create_tahfidz_groups_table.php
// Halaqah / kelompok tahfidz per tahun ajaran.
// =============================================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahfidz_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_unit_id');
            $table->uuid('school_id')->nullable();
            $table->uuid('academic_year_id');
            $table->uuid('tahfidz_package_id')->nullable();
            $table->uuid('curriculum_id')->nullable();
            $table->string('name', 100)->comment('Halaqah A, Halaqah Umar, dll');
            $table->string('code', 20)->nullable();
            $table->enum('gender', ['putra', 'putri']);
            $table->uuid('teacher_id')->comment('Guru Tahfidz pengampu');
            $table->uuid('coordinator_id')->nullable()->comment('Koordinator Tahfidz');
            $table->integer('max_members')->nullable();
            $table->string('room', 100)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('work_unit_id')->references('id')->on('work_units')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('tahfidz_package_id')->references('id')->on('tahfidz_packages')->nullOnDelete();
            $table->foreign('curriculum_id')->references('id')->on('tahfidz_curriculums')->nullOnDelete();
            $table->foreign('teacher_id')->references('id')->on('users');
            $table->foreign('coordinator_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['work_unit_id', 'academic_year_id', 'gender']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_groups');
    }
};
