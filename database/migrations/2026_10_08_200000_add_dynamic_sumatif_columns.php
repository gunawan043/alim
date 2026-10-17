<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom Sumatif Harian dinamis (additive).
     *
     * - teacher_admin_books.sumatif_columns : definisi kolom (JSON).
     *   null = default S1–S6 (kompatibilitas penuh).
     * - admin_nilai_sumatif.sumatif_harian   : nilai kolom BARU saja (JSON: {columnId: score}).
     *   Nilai S1–S6 tetap di kolom fisik lama, tidak digandakan.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('teacher_admin_books', 'sumatif_columns')) {
            Schema::table('teacher_admin_books', function (Blueprint $table) {
                $table->json('sumatif_columns')->nullable()->after('nr_final_weight_sas');
            });
        }

        if (! Schema::hasColumn('admin_nilai_sumatif', 'sumatif_harian')) {
            Schema::table('admin_nilai_sumatif', function (Blueprint $table) {
                $table->json('sumatif_harian')->nullable()->after('s6');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('teacher_admin_books', 'sumatif_columns')) {
            Schema::table('teacher_admin_books', function (Blueprint $table) {
                $table->dropColumn('sumatif_columns');
            });
        }

        if (Schema::hasColumn('admin_nilai_sumatif', 'sumatif_harian')) {
            Schema::table('admin_nilai_sumatif', function (Blueprint $table) {
                $table->dropColumn('sumatif_harian');
            });
        }
    }
};
