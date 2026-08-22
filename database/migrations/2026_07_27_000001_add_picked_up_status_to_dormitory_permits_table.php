<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite does not support ENUM or MODIFY COLUMN syntax
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE dormitory_permits MODIFY COLUMN status ENUM('pending','approved','rejected','returned','overdue','picked_up') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE dormitory_permits MODIFY COLUMN status ENUM('pending','approved','rejected','returned','overdue') NOT NULL DEFAULT 'pending'");
    }
};
