<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL-only: SQLite does not support MODIFY COLUMN
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Add 'picked_up' to status enum
        DB::statement("ALTER TABLE dormitory_permits MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'returned', 'overdue', 'picked_up') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Remove 'picked_up' from status enum
        DB::statement("ALTER TABLE dormitory_permits MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'returned', 'overdue') DEFAULT 'pending'");
    }
};
