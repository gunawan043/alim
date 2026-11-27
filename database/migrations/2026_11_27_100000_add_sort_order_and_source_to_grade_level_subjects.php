<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * URUTAN & ASAL MAPEL DI RAPOR — diatur sekali di Peta Kurikulum per tingkat.
 *
 *  - sort_order     : nomor urut tampil di rapor (kecil di atas). null = belum diatur.
 *  - subject_source : 'pondok' | 'pondok_dinas' | 'dinas'. Rapor mencetak blok Pondok lebih dulu,
 *                     lalu blok Dinas; di dalam blok mengikuti sort_order.
 *
 * Ditempatkan pada struktur tingkat (bukan pada master mapel) karena satu mapel
 * bisa berstatus pondok di satu tingkat dan dinas di tingkat lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('grade_level_subjects', 'sort_order')) {
            Schema::table('grade_level_subjects', function (Blueprint $table) {
                $table->unsignedSmallInteger('sort_order')->nullable()->after('track');
            });
        }

        if (! Schema::hasColumn('grade_level_subjects', 'subject_source')) {
            Schema::table('grade_level_subjects', function (Blueprint $table) {
                $table->string('subject_source', 10)->nullable()->after('sort_order')
                    ->comment('pondok | dinas | pondok_dinas');
            });
        }
    }

    public function down(): void
    {
        foreach (['subject_source', 'sort_order'] as $column) {
            if (Schema::hasColumn('grade_level_subjects', $column)) {
                Schema::table('grade_level_subjects', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
