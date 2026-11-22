<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Komponen nilai STS/SAS per buku mapel (mis. Praktik & Tulis).
 *
 * - teacher_admin_books.sts_columns / sas_columns : definisi komponen (JSON).
 *   null/kosong = fitur tidak aktif, input tunggal STS/SAS seperti sebelumnya.
 * - admin_nilai_sumatif.sts_components / sas_components : nilai komponen (JSON).
 *   Kolom sts/sas tetap menjadi nilai akhir (rata-rata komponen).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teacher_admin_books')) {
            Schema::table('teacher_admin_books', function (Blueprint $table) {
                if (! Schema::hasColumn('teacher_admin_books', 'sts_columns')) {
                    $table->json('sts_columns')->nullable()->after('sumatif_columns');
                }
                if (! Schema::hasColumn('teacher_admin_books', 'sas_columns')) {
                    $table->json('sas_columns')->nullable()->after('sts_columns');
                }
            });
        }

        if (Schema::hasTable('admin_nilai_sumatif')) {
            Schema::table('admin_nilai_sumatif', function (Blueprint $table) {
                if (! Schema::hasColumn('admin_nilai_sumatif', 'sts_components')) {
                    $table->json('sts_components')->nullable()->after('sts');
                }
                if (! Schema::hasColumn('admin_nilai_sumatif', 'sas_components')) {
                    $table->json('sas_components')->nullable()->after('sas');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admin_nilai_sumatif')) {
            Schema::table('admin_nilai_sumatif', function (Blueprint $table) {
                foreach (['sts_components', 'sas_components'] as $column) {
                    if (Schema::hasColumn('admin_nilai_sumatif', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('teacher_admin_books')) {
            Schema::table('teacher_admin_books', function (Blueprint $table) {
                foreach (['sts_columns', 'sas_columns'] as $column) {
                    if (Schema::hasColumn('teacher_admin_books', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
