<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daftar mapel rapor berlaku untuk satu TINGKAT, kecuali Kelas 11 & 12 yang
 * berlaku per ROMBEL karena ada penjurusan.
 *
 * Kolom `study_group_id` NULL berarti pengaturan tingkat (dipakai seluruh
 * rombel di tingkat itu). Terisi berarti pengaturan khusus rombel tersebut,
 * sehingga rombel Kelas 11 lain tidak ikut berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rapor_subjects')) {
            return;
        }

        if (! Schema::hasColumn('rapor_subjects', 'study_group_id')) {
            Schema::table('rapor_subjects', function (Blueprint $table) {
                $table->foreignUuid('study_group_id')
                    ->nullable()
                    ->after('grade_level_id')
                    ->constrained('study_groups')
                    ->cascadeOnDelete();

                $table->index(['study_group_id', 'academic_year_id', 'semester'], 'rapor_subjects_group_idx');
            });
        }

        if (! Schema::hasIndex('rapor_subjects', 'rapor_subjects_unique')) {
            return;
        }

        // Unik lama tidak membedakan rombel. Di MySQL indeks unik harus dilepas
        // lebih dulu; SQLite membuangnya bersama indeks tabel (tidak didukung).
        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('rapor_subjects', function (Blueprint $table) {
                $table->dropUnique('rapor_subjects_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('rapor_subjects') || ! Schema::hasColumn('rapor_subjects', 'study_group_id')) {
            return;
        }

        Schema::table('rapor_subjects', function (Blueprint $table) {
            $table->dropIndex('rapor_subjects_group_idx');
            $table->dropConstrainedForeignId('study_group_id');
        });
    }
};
