<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// =============================================================================
// 26. create_tahfidz_mutabaah_table.php
// Buku pantau ibadah harian santri. Dicatat musyrif per hari.
// =============================================================================
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tahfidz_mutabaah', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('dormitory_id')->nullable();
            $table->uuid('tahfidz_group_id')->nullable();
            $table->uuid('academic_year_id');
            $table->uuid('recorded_by')->comment('Musyrif / wali kamar yang mencatat');
            $table->date('record_date');

            // --- SHOLAT FARDHU ---
            $table->enum('sholat_subuh', ['berjamaah', 'sendiri', 'qadha', 'tidak', 'uzur'])->nullable();
            $table->enum('sholat_dzuhur', ['berjamaah', 'sendiri', 'qadha', 'tidak', 'uzur'])->nullable();
            $table->enum('sholat_ashar', ['berjamaah', 'sendiri', 'qadha', 'tidak', 'uzur'])->nullable();
            $table->enum('sholat_maghrib', ['berjamaah', 'sendiri', 'qadha', 'tidak', 'uzur'])->nullable();
            $table->enum('sholat_isya', ['berjamaah', 'sendiri', 'qadha', 'tidak', 'uzur'])->nullable();

            // --- IBADAH SUNNAH ---
            $table->tinyInteger('sholat_tahajud')->default(0);
            $table->tinyInteger('sholat_dhuha')->default(0);
            $table->tinyInteger('puasa_sunnah')->default(0);
            $table->tinyInteger('sedekah')->default(0);

            // --- TILAWAH & HAFALAN MANDIRI ---
            $table->integer('tilawah_halaman')->default(0)
                  ->comment('Halaman tilawah mandiri hari ini');
            $table->integer('tikror_mandiri_halaman')->default(0)
                  ->comment('Halaman murajaah/tikror mandiri');
            $table->tinyInteger('wirid_pagi')->default(0);
            $table->tinyInteger('wirid_sore')->default(0);

            $table->text('catatan_musyrif')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('dormitory_id')->references('id')->on('dormitories')->nullOnDelete();
            $table->foreign('tahfidz_group_id')->references('id')->on('tahfidz_groups')->nullOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users');
            $table->unique(['student_id', 'record_date'], 'unique_mutabaah_per_day');
            $table->index(
                ['student_id', 'academic_year_id'],
                'idx_std_acy_2'
            );
            $table->index(
                ['recorded_by', 'record_date'],
                'idx_rec_date'
            );

        });
    }
    public function down(): void { Schema::dropIfExists('tahfidz_mutabaah'); }
};
