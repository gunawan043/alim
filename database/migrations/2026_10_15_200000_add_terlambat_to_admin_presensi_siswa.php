<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `admin_presensi_siswa` MODIFY COLUMN `status` ENUM('hadir','terlambat','izin','sakit','alpa') NOT NULL DEFAULT 'hadir'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `admin_presensi_siswa` MODIFY COLUMN `status` ENUM('hadir','izin','sakit','alpa') NOT NULL DEFAULT 'hadir'");
        }
    }
};
