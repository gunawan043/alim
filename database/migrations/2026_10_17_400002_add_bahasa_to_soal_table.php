<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bahasa soal: latin | arab | gabungan.
 *
 * Menentukan arah tata letak di lembar soal:
 *  - latin/gabungan → LTR, huruf pilihan A/B/C/D;
 *  - arab           → RTL, huruf pilihan ا/ب/ج/د.
 * Aditif & nullable (soal lama tetap memakai deteksi otomatis).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('soal') && ! Schema::hasColumn('soal', 'bahasa')) {
            Schema::table('soal', function (Blueprint $table) {
                $table->string('bahasa', 12)->nullable()->after('tipe_soal');
                $table->index('bahasa', 'soal_bahasa_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('soal') && Schema::hasColumn('soal', 'bahasa')) {
            Schema::table('soal', function (Blueprint $table) {
                $table->dropIndex('soal_bahasa_idx');
                $table->dropColumn('bahasa');
            });
        }
    }
};
