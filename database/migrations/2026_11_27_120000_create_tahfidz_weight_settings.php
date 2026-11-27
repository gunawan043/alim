<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bobot NR Final mapel tahfidz diatur DEPARTEMEN, bukan guru.
 *
 * Satu baris per (sekolah × tahun ajaran × semester). Bobot dibedakan karena
 * semester ganjil tidak mengenal SAS. Buku halaqah tetap menyimpan kolom
 * weight_* sebagai salinan hasil, sehingga angka yang dipakai rapor dan
 * leger selalu sama untuk seluruh halaqah di sekolah yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tahfidz_weight_settings')) {
            return;
        }

        Schema::create('tahfidz_weight_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_id');
            $table->uuid('academic_year_id');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->decimal('weight_npt', 4, 1)->default(0);
            $table->decimal('weight_ziyadah', 4, 1)->default(0);
            $table->decimal('weight_rabt', 4, 1)->default(0);
            $table->decimal('weight_tikrar', 4, 1)->default(0);
            $table->decimal('weight_sts', 4, 1)->default(0);
            $table->decimal('weight_sas', 4, 1)->default(0);
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();

            $table->unique(['school_id', 'academic_year_id', 'semester'], 'unique_tahfidz_weight_per_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_weight_settings');
    }
};
