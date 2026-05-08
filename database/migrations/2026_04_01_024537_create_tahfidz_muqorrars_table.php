<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// =============================================================================
// 5. create_tahfidz_muqorrars_table.php
// RPP Tahfizh bulanan — rencana pembelajaran per bulan per paket/kelas.
// Implementasi digital dari Gambar 1: Tabel Penyusunan RPP Tahfizh.
// Kolom: Paket, Kelas/Semester, Bulan, Target Hafalan, Jumlah Halaman,
//         Baris/Bulan, Baris/Hari, Hari Aktif.
// =============================================================================
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tahfidz_muqorrars', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_unit_id');
            $table->uuid('school_id')->nullable();
            $table->uuid('curriculum_id')->nullable();
            $table->uuid('academic_year_id');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->string('package_name', 100)->nullable()
                  ->comment('Nama paket: Tahfidz Reguler, Intensif, dll');
            $table->string('grade_class', 50)->nullable()
                  ->comment('Kelas: 7A, 8B, Kelas Takhassus, dll');
            $table->tinyInteger('bulan_kbm')
                  ->comment('Bulan ke-berapa dalam kalender: 1=Januari, 7=Juli, dst');
            $table->year('bulan_kbm_tahun');
            $table->integer('target_bulanan_halaman')->nullable();
            $table->integer('target_bulanan_baris')->nullable();
            $table->integer('target_harian_baris')->nullable();
            $table->integer('jumlah_hari_aktif')->nullable();
            $table->smallInteger('surah_mulai_id')->unsigned()->nullable();
            $table->integer('ayat_mulai')->nullable();
            $table->smallInteger('surah_selesai_id')->unsigned()->nullable();
            $table->integer('ayat_selesai')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('work_unit_id')->references('id')->on('work_units')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('curriculum_id')->references('id')->on('tahfidz_curriculums')->nullOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('surah_mulai_id')->references('id')->on('tahfidz_surah_master')->nullOnDelete();
            $table->foreign('surah_selesai_id')->references('id')->on('tahfidz_surah_master')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['academic_year_id', 'semester', 'bulan_kbm']);
        });
    }
    public function down(): void { Schema::dropIfExists('tahfidz_muqorrars'); }
};
