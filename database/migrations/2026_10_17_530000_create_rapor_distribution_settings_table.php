<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V4 — Pengaturan tanggal pembagian rapor per satuan pendidikan + TA + semester.
 * Tanggal ini dipakai pada dokumen rapor (cetak & arsip) sebagai tanggal
 * penerbitan/pembagian, menggantikan tanggal cetak (now()).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rapor_distribution_settings')) {
            Schema::create('rapor_distribution_settings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->enum('semester', ['ganjil', 'genap']);
                $table->date('distribution_date')->nullable();
                $table->string('city', 100)->nullable();
                $table->text('notes')->nullable();
                $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(
                    ['school_id', 'academic_year_id', 'semester'],
                    'rapor_distribution_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rapor_distribution_settings');
    }
};
