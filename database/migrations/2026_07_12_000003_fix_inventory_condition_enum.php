<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL-only: SQLite does not support MODIFY COLUMN
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE dormitory_inventories MODIFY COLUMN `condition` ENUM('baik','rusak','perbaikan','hilang') NOT NULL DEFAULT 'baik'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE dormitory_inventories MODIFY COLUMN `condition` ENUM('baik','rusak_ringan','rusak_berat','hilang') NOT NULL DEFAULT 'baik'");
    }
};
