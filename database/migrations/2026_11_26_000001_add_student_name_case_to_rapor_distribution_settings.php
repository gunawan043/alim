<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Format nama santri pada cetak rapor.
 *
 * Nilai:
 *   as_is  → tampil persis seperti data (perilaku lama)
 *   upper  → HURUF BESAR SEMUA
 *   title  → Kapital Di Awal Kata
 *
 * Satu pengaturan per sekolah per semester (bukan per varian dokumen) agar
 * nama santri konsisten di seluruh rapor sekolah tersebut.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rapor_distribution_settings')) {
            return;
        }

        if (! Schema::hasColumn('rapor_distribution_settings', 'student_name_case')) {
            Schema::table('rapor_distribution_settings', function (Blueprint $table) {
                $table->string('student_name_case', 10)->default('as_is');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rapor_distribution_settings')
            && Schema::hasColumn('rapor_distribution_settings', 'student_name_case')) {
            Schema::table('rapor_distribution_settings', function (Blueprint $table) {
                $table->dropColumn('student_name_case');
            });
        }
    }
};
