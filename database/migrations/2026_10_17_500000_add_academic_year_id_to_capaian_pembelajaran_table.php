<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ARSIP PERIODE — Capaian Pembelajaran (CP) per tahun ajaran.
 *
 * CP sebelumnya global per mapel+fase. Kolom `academic_year_id` (nullable)
 * membuat CP dapat dimiliki per periode; baris lama (NULL) tetap berlaku
 * sebagai fallback lintas periode (baris versi TA diprioritaskan).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('capaian_pembelajaran', 'academic_year_id')) {
            Schema::table('capaian_pembelajaran', function (Blueprint $table) {
                $table->foreignUuid('academic_year_id')->nullable()->after('subject_id')
                    ->constrained('academic_years')->cascadeOnDelete();
                $table->index(['academic_year_id', 'subject_id', 'fase'], 'cp_ay_subject_fase_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('capaian_pembelajaran', 'academic_year_id')) {
            Schema::table('capaian_pembelajaran', function (Blueprint $table) {
                $table->dropIndex('cp_ay_subject_fase_idx');
                $table->dropConstrainedForeignId('academic_year_id');
            });
        }
    }
};
