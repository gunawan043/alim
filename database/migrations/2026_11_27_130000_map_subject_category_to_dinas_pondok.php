<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori mapel kini menyatakan asal kurikulum: Dinas, Pondok, atau
 * Dinas + Pondok — menggantikan Nasional / Lokal / Muatan Lokal.
 *
 * Kolom `category` dibuat sebagai ENUM ('nasional','lokal','muatan_lokal'),
 * sehingga SQLite memberi CHECK constraint dan MySQL memakai ENUM. Nilai baru
 * akan ditolak sampai kolomnya diganti menjadi STRING biasa.
 *
 * Pemetaan data lama:
 *   nasional      → dinas   (kurikulum pemerintah)
 *   lokal         → pondok  (mapel khas yayasan/pondok)
 *   muatan_lokal  → pondok
 *
 * Urutan penting: longgarkan tipe kolom DULU (supaya nilai baru diterima),
 * baru petakan data.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subjects') || ! Schema::hasColumn('subjects', 'category')) {
            return;
        }

        // SQLite tidak mendukung ALTER COLUMN; Laravel menangani lewat
        // rebuild tabel. MySQL cukup mengubah tipe menjadi VARCHAR.
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('category', 20)->default('dinas')->change();
        });

        DB::table('subjects')->where('category', 'nasional')->update(['category' => 'dinas']);
        DB::table('subjects')->whereIn('category', ['lokal', 'muatan_lokal'])->update(['category' => 'pondok']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('subjects') || ! Schema::hasColumn('subjects', 'category')) {
            return;
        }

        // Nilai "dinas + pondok" tidak punya padanan lama; dipulihkan ke lokal.
        DB::table('subjects')->where('category', 'dinas')->update(['category' => 'nasional']);
        DB::table('subjects')->whereIn('category', ['pondok', 'dinas_pondok'])->update(['category' => 'lokal']);

        Schema::table('subjects', function (Blueprint $table) {
            $table->string('category', 20)->default('nasional')->change();
        });
    }
};
