<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// =============================================================================
// 17. create_tahfidz_tasmian_sessions_table.php
// Sesi/event tasmi'an — ujian hafalan formal internal.
// Berbeda dari setoran harian. Ada mustami', sertifikat, komponen nilai terstruktur.
// =============================================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahfidz_tasmian_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_unit_id');
            $table->uuid('school_id')->nullable();
            $table->uuid('academic_year_id');
            $table->string('session_name', 191);
            $table->date('session_date');
            $table->time('session_time_start')->nullable();
            $table->time('session_time_end')->nullable();
            $table->string('location', 191)->nullable();
            $table->enum('session_type', ['reguler', 'kenaikan_juz', 'khatam', 'khusus'])->default('reguler');
            $table->enum('status', ['draft', 'terjadwal', 'berlangsung', 'selesai'])->default('draft');
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('work_unit_id')->references('id')->on('work_units')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
            $table->index(
                ['work_unit_id', 'academic_year_id', 'session_date'],
                'idx_wu_acy_sess'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_tasmian_sessions');
    }
};
