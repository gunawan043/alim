<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kelompok rumpun mapel:
 *  - agama : Fikih, Ushul Fikih, Aqidah, Adab, Tahfidz, Hadits, ...
 *  - bahasa: Bahasa Arab, Bahasa Indonesia, Bahasa Inggris, ...
 *  - umum  : Matematika, IPAS, PJOK, PKn, Seni Budaya, ...
 *
 * Sebelumnya pengelompokan dilakukan lewat pencocokan nama mapel sehingga
 * mapel seperti Fikih jatuh ke kelompok Umum. Kolom ini disimpan eksplisit
 * agar pengelompokan konsisten dan bisa diubah pengguna.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('subjects', 'kelompok')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->string('kelompok', 20)->default('umum')->after('category');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('subjects', 'kelompok')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropColumn('kelompok');
            });
        }
    }
};
