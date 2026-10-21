<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! Schema::hasTable('uks_patients')) {
            return;
        }

        DB::statement("ALTER TABLE uks_patients MODIFY COLUMN status ENUM(
            'menunggu_pemeriksaan',
            'sedang_ditangani',
            'observasi',
            'rawat_uks',
            'istirahat_di_uks',
            'kembali_ke_asrama',
            'kembali_ke_sekolah',
            'dijemput_wali',
            'pulang',
            'dirujuk_ke_klinik',
            'dirujuk_ke_rumah_sakit',
            'selesai'
        ) DEFAULT 'menunggu_pemeriksaan'");
    }

    public function down(): void
    {
        // Sengaja tidak mengembalikan enum lama: data baru memakai status klinis.
    }
};
