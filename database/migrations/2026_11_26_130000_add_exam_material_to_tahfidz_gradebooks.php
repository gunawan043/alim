<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Materi ujian STS dan SAS per buku nilai (halaqah + mapel + semester).
 *
 * Nilai STS/SAS saja tidak cukup: guru dan wali perlu tahu ayat mana yang
 * diujikan. Materi disimpan di buku, bukan di baris nilai, karena satu
 * halaqah diuji dengan materi yang sama.
 *
 * Rentangnya memakai bentuk yang sama dengan setoran hafalan: surah awal,
 * ayat awal, surah akhir, ayat akhir, dan juz. Kolom halaman/baris ikut
 * disimpan agar cetak daftar ujian bisa menyebut posisi Mushaf Madinah.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tahfidz_gradebooks')) {
            return;
        }

        Schema::table('tahfidz_gradebooks', function (Blueprint $table) {
            if (! Schema::hasColumn('tahfidz_gradebooks', 'sts_material')) {
                // Ringkasan materi ujian STS, mis. "Juz 30 · An-Naba 1 – An-Nas 6".
                $table->string('sts_material', 191)->nullable()->after('weight_sas');
                $table->string('sts_surah_start', 100)->nullable()->after('sts_material');
                $table->unsignedSmallInteger('sts_ayah_start')->nullable()->after('sts_surah_start');
                $table->string('sts_surah_end', 100)->nullable()->after('sts_ayah_start');
                $table->unsignedSmallInteger('sts_ayah_end')->nullable()->after('sts_surah_end');
                $table->unsignedTinyInteger('sts_juz')->nullable()->after('sts_ayah_end');
                $table->unsignedSmallInteger('sts_page_start')->nullable()->after('sts_juz');
                $table->unsignedTinyInteger('sts_line_start')->nullable()->after('sts_page_start');
                $table->unsignedSmallInteger('sts_page_end')->nullable()->after('sts_line_start');
                $table->unsignedTinyInteger('sts_line_end')->nullable()->after('sts_page_end');
            }

            if (! Schema::hasColumn('tahfidz_gradebooks', 'sas_material')) {
                $table->string('sas_material', 191)->nullable()->after('sts_line_end');
                $table->string('sas_surah_start', 100)->nullable()->after('sas_material');
                $table->unsignedSmallInteger('sas_ayah_start')->nullable()->after('sas_surah_start');
                $table->string('sas_surah_end', 100)->nullable()->after('sas_ayah_start');
                $table->unsignedSmallInteger('sas_ayah_end')->nullable()->after('sas_surah_end');
                $table->unsignedTinyInteger('sas_juz')->nullable()->after('sas_ayah_end');
                $table->unsignedSmallInteger('sas_page_start')->nullable()->after('sas_juz');
                $table->unsignedTinyInteger('sas_line_start')->nullable()->after('sas_page_start');
                $table->unsignedSmallInteger('sas_page_end')->nullable()->after('sas_line_start');
                $table->unsignedTinyInteger('sas_line_end')->nullable()->after('sas_page_end');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tahfidz_gradebooks')) {
            return;
        }

        Schema::table('tahfidz_gradebooks', function (Blueprint $table) {
            foreach ([
                'sts_material', 'sts_surah_start', 'sts_ayah_start', 'sts_surah_end', 'sts_ayah_end',
                'sts_juz', 'sts_page_start', 'sts_line_start', 'sts_page_end', 'sts_line_end',
                'sas_material', 'sas_surah_start', 'sas_ayah_start', 'sas_surah_end', 'sas_ayah_end',
                'sas_juz', 'sas_page_start', 'sas_line_start', 'sas_page_end', 'sas_line_end',
            ] as $column) {
                if (Schema::hasColumn('tahfidz_gradebooks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
