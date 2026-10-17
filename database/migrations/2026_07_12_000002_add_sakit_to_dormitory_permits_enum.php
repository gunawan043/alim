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

        DB::statement("ALTER TABLE dormitory_permits MODIFY COLUMN permit_type ENUM('pulang','keluar_kota','berobat','sakit','keperluan_keluarga','lainnya') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE dormitory_permits MODIFY COLUMN permit_type ENUM('pulang','keluar_kota','berobat','keperluan_keluarga','lainnya') NOT NULL");
    }
};
