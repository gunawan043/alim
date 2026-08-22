<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Expand status enum to include discharge/return statuses.
     * Only runs if uks_patients table exists.
     */
    public function up(): void
    {
        // SQLite does not support ENUM or MODIFY COLUMN syntax
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('uks_patients')) {
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
    }

    public function down(): void
    {
        // SQLite does not support ENUM or MODIFY COLUMN syntax
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Rollback only if table exists - use the simpler enum from previous migration
        if (Schema::hasTable('uks_patients')) {
            DB::statement("ALTER TABLE uks_patients MODIFY COLUMN status ENUM(
                'menunggu_pemeriksaan',
                'sedang_ditangani',
                'observasi',
                'rawat_uks',
                'dirujuk_ke_klinik',
                'dirujuk_ke_rumah_sakit',
                'selesai'
            ) DEFAULT 'menunggu_pemeriksaan'");
        }
    }
};
