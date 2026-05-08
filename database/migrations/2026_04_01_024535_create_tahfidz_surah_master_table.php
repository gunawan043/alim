<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// =============================================================================
// 2. create_tahfidz_surah_master_table.php
// Master data 114 surah Al-Qur'an. Di-seed sekali, tidak berubah.
// Dipakai sebagai referensi posisi hafalan (surah_start, surah_end).
// =============================================================================
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tahfidz_surah_master', function (Blueprint $table) {
            $table->smallInteger('id')->unsigned()->primary();
            $table->tinyInteger('number')->unsigned()->comment('Nomor surah 1-114');
            $table->string('name_arabic', 100);
            $table->string('name_latin', 100);
            $table->tinyInteger('juz')->unsigned()->comment('Juz utama surah ini');
            $table->integer('total_ayat');
            $table->decimal('total_halaman', 4, 1)->nullable();
            $table->integer('halaman_start')->nullable()
                  ->comment('Halaman mulai di mushaf standar 604 halaman');
            $table->integer('halaman_end')->nullable();
            $table->enum('revelation_type', ['makkiyah', 'madaniyah'])->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('tahfidz_surah_master'); }
};
