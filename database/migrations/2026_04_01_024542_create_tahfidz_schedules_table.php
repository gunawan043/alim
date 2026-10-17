<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// =============================================================================
// 9. create_tahfidz_schedules_table.php
// Jadwal halaqah per kelompok — hari, jam, jenis sesi.
// =============================================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahfidz_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tahfidz_group_id');
            $table->uuid('academic_year_id');
            $table->tinyInteger('day_of_week')->comment('1=Senin-7=Minggu');
            $table->time('time_start');
            $table->time('time_end');
            $table->string('room', 100)->nullable();
            $table->enum('schedule_type', ['setoran', 'murajaah', 'tasmi', 'evaluasi', 'tikror'])->default('setoran');
            $table->tinyInteger('is_active')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tahfidz_group_id')->references('id')->on('tahfidz_groups')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->index(['tahfidz_group_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_schedules');
    }
};
