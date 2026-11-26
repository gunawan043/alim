<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Format penilaian mapel: `umum` atau `tahfidz`.
 *
 * Sebelumnya status tahfidz ditebak dari nama/kode mapel di dua tempat berbeda
 * (TahfidzRaporService & JadwalGeneratorService) dengan aturan yang tidak sama,
 * sehingga mapel bernama "Hafalan Quran" masuk jalur tahfidz pada rapor tetapi
 * tidak pada penjadwalan. Penanda ini disimpan eksplisit agar satu mapel hanya
 * punya satu format penilaian dan tidak pernah ditebak saat menghitung nilai.
 *
 * Default `umum` menjaga kompatibilitas: mapel yang sudah ada tidak berubah
 * perilakunya sampai admin menandainya lewat halaman verifikasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('subjects', 'penilaian_format')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->string('penilaian_format', 20)->default('umum');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('subjects', 'penilaian_format')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropColumn('penilaian_format');
            });
        }
    }
};
