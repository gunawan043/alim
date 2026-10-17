<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// =============================================================================
// 6. create_tahfidz_muqorrar_details_table.php
// Rincian target per pekan dalam satu muqorrar.
// Implementasi: baris per baris tabel RPP (Target Pekanan, Hari, Target Harian).
// =============================================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahfidz_muqorrar_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('muqorrar_id');
            $table->tinyInteger('week_number')->comment('Pekan ke-1, 2, 3, 4 dalam bulan');
            $table->string('hari_pembelajaran', 100)->nullable()
                ->comment('Misal: Senin-Jumat, Setiap Hari, Senin/Rabu/Jumat');
            $table->decimal('target_pekanan_halaman', 5, 1)->nullable();
            $table->integer('target_pekanan_baris')->nullable();
            $table->string('target_harian_pekanan', 191)->nullable()
                ->comment('Deskripsi target harian untuk pekan ini');
            $table->smallInteger('surah_start_id')->unsigned()->nullable();
            $table->integer('ayat_start')->nullable();
            $table->smallInteger('surah_end_id')->unsigned()->nullable();
            $table->integer('ayat_end')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('muqorrar_id')->references('id')->on('tahfidz_muqorrars')->cascadeOnDelete();
            $table->foreign('surah_start_id')->references('id')->on('tahfidz_surah_master')->nullOnDelete();
            $table->foreign('surah_end_id')->references('id')->on('tahfidz_surah_master')->nullOnDelete();
            $table->unique(['muqorrar_id', 'week_number'], 'unique_week_per_muqorrar');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_muqorrar_details');
    }
};
