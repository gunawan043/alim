<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only run if uks_patients table exists
        if (Schema::hasTable('uks_patients')) {
            // Expand status enum from ['aktif','selesai','dirujuk'] to include the full treatment pipeline
            // MySQL 8 enum columns need column recreation for adding values
            DB::statement("ALTER TABLE uks_patients MODIFY COLUMN status ENUM(
                'menunggu_pemeriksaan',
                'sedang_ditangani',
                'observasi',
                'rawat_uks',
                'dirujuk_ke_klinik',
                'dirujuk_ke_rumah_sakit',
                'selesai'
            ) DEFAULT 'menunggu_pemeriksaan'");

            // Update existing enum values to new format
            DB::table('uks_patients')->where('status', 'aktif')->update(['status' => 'sedang_ditangani']);
            DB::table('uks_patients')->where('status', 'selesai')->update(['status' => 'selesai']);
            DB::table('uks_patients')->where('status', 'dirujuk')->update(['status' => 'dirujuk_ke_klinik']);
        }
    }

    public function down(): void
    {
        // Only run if uks_patients table exists
        if (Schema::hasTable('uks_patients')) {
            // Rollback to old enum format
            DB::statement("ALTER TABLE uks_patients MODIFY COLUMN status ENUM('aktif', 'selesai', 'dirujuk') DEFAULT 'aktif'");

            // Convert back existing values
            DB::table('uks_patients')->where('status', 'sedang_ditangani')->update(['status' => 'aktif']);
            DB::table('uks_patients')->where('status', 'menunggu_pemeriksaan')->update(['status' => 'aktif']);
            DB::table('uks_patients')->where('status', 'observasi')->update(['status' => 'aktif']);
            DB::table('uks_patients')->where('status', 'rawat_uks')->update(['status' => 'aktif']);
            DB::table('uks_patients')->where('status', 'dirujuk_ke_klinik')->update(['status' => 'dirujuk']);
            DB::table('uks_patients')->where('status', 'dirujuk_ke_rumah_sakit')->update(['status' => 'dirujuk']);
        }
    }
};
