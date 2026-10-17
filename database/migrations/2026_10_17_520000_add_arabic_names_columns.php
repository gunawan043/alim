<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V4 — Nama Arab untuk dokumen rapor multibahasa.
 *
 * Input nama Arab berada di menu data masing-masing (bukan menu rapor):
 *  - subjects.name_ar       → nama mata pelajaran (menu Data Mapel)
 *  - students.name_ar       → nama santri (menu Data Santri)
 *  - users.name_ar          → nama guru/wali kelas (menu GTK)
 *  - schools.principal_name_ar → nama kepala satuan pendidikan (menu Sekolah)
 *
 * Nilai, predikat, KKTP, dan tanggal TIDAK perlu input — dikonversi otomatis.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('subjects', 'name_ar')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->string('name_ar', 100)->nullable()->after('name');
            });
        }

        if (! Schema::hasColumn('students', 'name_ar')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('name_ar', 255)->nullable()->after('name');
            });
        }

        if (! Schema::hasColumn('users', 'name_ar')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('name_ar', 255)->nullable()->after('name');
            });
        }

        if (! Schema::hasColumn('schools', 'principal_name_ar')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->string('principal_name_ar', 255)->nullable()->after('principal_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('subjects', 'name_ar')) {
            Schema::table('subjects', fn (Blueprint $table) => $table->dropColumn('name_ar'));
        }
        if (Schema::hasColumn('students', 'name_ar')) {
            Schema::table('students', fn (Blueprint $table) => $table->dropColumn('name_ar'));
        }
        if (Schema::hasColumn('users', 'name_ar')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('name_ar'));
        }
        if (Schema::hasColumn('schools', 'principal_name_ar')) {
            Schema::table('schools', fn (Blueprint $table) => $table->dropColumn('principal_name_ar'));
        }
    }
};
