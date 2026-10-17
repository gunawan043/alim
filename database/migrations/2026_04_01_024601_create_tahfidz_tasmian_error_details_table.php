<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// =============================================================================
// 20. create_tahfidz_tasmian_error_details_table.php
// Detail jenis kesalahan per penilaian tasmi'an.
// Implementasi: Formulir Penilaian Bidang Tajwid (Gambar 5).
// Sistem pengurangan: nilai_awal - Σ(error_count × deduction_per_error).
// Ringan (×0.5) vs Berat (×1) sesuai bobot di Gambar 5.
// =============================================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahfidz_tasmian_error_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tasmian_score_id');
            $table->enum('komponen', ['tajwid', 'fashohah', 'tahfizh']);
            $table->string('error_type', 100)
                ->comment('makharijul_huruf, shifatul_huruf, mad_wajib, mad_jaiz,
                   ghunnah, idgham, ikhfa, iqlab, qalqalah,
                   waqaf_ibtida, tasydid, hamzah, lahn_jali, lahn_khafi');
            $table->enum('error_level', ['ringan', 'berat'])
                ->comment('Ringan = deduction ×0.5 | Berat = deduction ×1');
            $table->tinyInteger('error_count')->default(0)
                ->comment('Jumlah kesalahan jenis ini (kolom 1-4 pada Gambar 5)');
            $table->decimal('deduction_per_error', 4, 2)->default(0.5)
                ->comment('0.5 untuk ringan, 1.0 untuk berat');
            $table->decimal('total_deduction', 5, 2)->default(0)
                ->comment('= error_count × deduction_per_error');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('tasmian_score_id')->references('id')->on('tahfidz_tasmian_scores')->cascadeOnDelete();
            $table->index(
                ['tasmian_score_id', 'komponen'],
                'idx_tasm_score_comp'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_tasmian_error_details');
    }
};
