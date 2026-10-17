<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bahasa naskah tingkat UJIAN: latin | gabungan | arab.
 *
 * Menentukan format seluruh lembar soal:
 *  - latin/gabungan → LTR, nomor Latin, pilihan A/B/C/D;
 *  - arab           → RTL, nomor Arab (١، ٢، ٣), pilihan ا/ب/ج/د.
 * Aditif & nullable (ujian lama memakai turunan otomatis dari butir soal).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ljk_exams') && ! Schema::hasColumn('ljk_exams', 'bahasa')) {
            Schema::table('ljk_exams', function (Blueprint $table) {
                $table->string('bahasa', 12)->nullable()->after('jenis_ujian');
                $table->index('bahasa', 'ljk_exams_bahasa_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ljk_exams') && Schema::hasColumn('ljk_exams', 'bahasa')) {
            Schema::table('ljk_exams', function (Blueprint $table) {
                $table->dropIndex('ljk_exams_bahasa_idx');
                $table->dropColumn('bahasa');
            });
        }
    }
};
