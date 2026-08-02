<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL-only: SQLite has no ENUM type and no MODIFY COLUMN syntax.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // MySQL ENUM tidak bisa di-"add" langsung, perlu modify
        DB::statement("ALTER TABLE admin_presensi_harian
            MODIFY COLUMN status ENUM('hadir','terlambat','izin','sakit','alpa')
            NOT NULL DEFAULT 'hadir'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE admin_presensi_harian
            MODIFY COLUMN status ENUM('hadir','izin','sakit','alpa')
            NOT NULL DEFAULT 'hadir'");
    }
};
