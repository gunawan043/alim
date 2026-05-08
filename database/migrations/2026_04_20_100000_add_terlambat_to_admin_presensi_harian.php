<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL ENUM tidak bisa di-"add" langsung, perlu modify
        DB::statement("ALTER TABLE admin_presensi_harian
            MODIFY COLUMN status ENUM('hadir','terlambat','izin','sakit','alpa')
            NOT NULL DEFAULT 'hadir'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE admin_presensi_harian
            MODIFY COLUMN status ENUM('hadir','izin','sakit','alpa')
            NOT NULL DEFAULT 'hadir'");
    }
};
