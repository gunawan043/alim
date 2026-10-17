<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ARSIP PETA KURIKULUM: struktur mapel per tingkat (alokasi JP) kini
 * dapat dimiliki per tahun ajaran sehingga perubahan TA baru tidak
 * menimpa arsip TA sebelumnya.
 *
 * Nilai NULL = struktur lama ("berlaku umum") yang dibuat sebelum
 * versi periode aktif — dipertahankan agar arsip TA lama tetap tampil
 * tanpa membuat data historis palsu. Baris ber-academic_year_id
 * (versi periode) selalu diprioritaskan atas baris NULL.
 *
 * Keunikan struktur diperluas: (grade_level_id, subject_id, track)
 * → (academic_year_id, grade_level_id, subject_id, track).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('grade_level_subjects', 'academic_year_id')) {
            Schema::table('grade_level_subjects', function (Blueprint $table) {
                $table->foreignUuid('academic_year_id')->nullable()->after('grade_level_id')
                    ->constrained('academic_years')->cascadeOnDelete();
                $table->index(['academic_year_id', 'grade_level_id'], 'gls_academic_year_grade_idx');
            });
        }

        try {
            Schema::table('grade_level_subjects', function (Blueprint $table) {
                $table->dropUnique('unique_grade_level_subject_track');
            });
        } catch (Throwable) {
            // Index tidak ada / sudah diganti pada driver ini.
        }

        try {
            Schema::table('grade_level_subjects', function (Blueprint $table) {
                $table->unique(
                    ['academic_year_id', 'grade_level_id', 'subject_id', 'track'],
                    'unique_gls_ay_grade_subject_track'
                );
            });
        } catch (Throwable) {
            // Sudah ada.
        }
    }

    public function down(): void
    {
        try {
            Schema::table('grade_level_subjects', function (Blueprint $table) {
                $table->dropUnique('unique_gls_ay_grade_subject_track');
                $table->unique(['grade_level_id', 'subject_id', 'track'], 'unique_grade_level_subject_track');
            });
        } catch (Throwable) {
            // abaikan
        }

        if (Schema::hasColumn('grade_level_subjects', 'academic_year_id')) {
            Schema::table('grade_level_subjects', function (Blueprint $table) {
                $table->dropIndex('gls_academic_year_grade_idx');
                $table->dropConstrainedForeignId('academic_year_id');
            });
        }
    }
};
