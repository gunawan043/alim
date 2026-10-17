<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tahfidz_uthq_error_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assessment_id');
            $table->enum('komponen', ['tajwid', 'fashohah', 'tahfizh']);
            $table->string('error_type', 100)
                ->comment('makharijul_huruf, shifatul_huruf, mad, ghunnah, idgham, ikhfa, iqlab, qalqalah, waqaf_ibtida, tasydid, hamzah, lahn_jali, lahn_khafi');
            $table->enum('error_level', ['ringan', 'berat'])
                ->comment('Ringan=-1, Berat=-2 sesuai header Gambar 4');
            $table->tinyInteger('error_count')->default(0);
            $table->decimal('deduction_per_error', 4, 2)->default(1.0)
                ->comment('1 untuk ringan, 2 untuk berat — sesuai bobot UTHQ');
            $table->decimal('total_deduction', 5, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->foreign('assessment_id')->references('id')->on('tahfidz_uthq_assessments')->cascadeOnDelete();
            $table->index(
                ['assessment_id', 'komponen'],
                'idx_assess_comp'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tahfidz_uthq_error_details');
    }
};
