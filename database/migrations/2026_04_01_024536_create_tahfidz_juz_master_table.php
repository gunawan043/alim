<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// =============================================================================
// 3. create_tahfidz_juz_master_table.php
// Master data 30 juz. Di-seed sekali. Dipakai untuk progress map visualisasi.
// =============================================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahfidz_juz_master', function (Blueprint $table) {
            $table->tinyInteger('id')->unsigned()->primary();
            $table->tinyInteger('juz_number')->unsigned()->unique();
            $table->string('name', 50)->nullable()->comment('Misal: Juz Amma, Juz Tabarak');
            $table->integer('halaman_start');
            $table->integer('halaman_end');
            $table->integer('total_halaman');
            $table->smallInteger('surah_start_id')->unsigned()->nullable();
            $table->integer('ayat_start')->nullable();
            $table->smallInteger('surah_end_id')->unsigned()->nullable();
            $table->integer('ayat_end')->nullable();
            $table->timestamps();

            $table->foreign('surah_start_id')->references('id')->on('tahfidz_surah_master')->nullOnDelete();
            $table->foreign('surah_end_id')->references('id')->on('tahfidz_surah_master')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_juz_master');
    }
};
