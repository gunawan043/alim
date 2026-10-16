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
    }

    public function down(): void
    {
        if (Schema::hasColumn('grade_level_subjects', 'academic_year_id')) {
            Schema::table('grade_level_subjects', function (Blueprint $table) {
                $table->dropIndex('gls_academic_year_grade_idx');
                $table->dropConstrainedForeignId('academic_year_id');
            });
        }
    }
};
