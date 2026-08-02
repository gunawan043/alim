<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE dormitory_permits MODIFY COLUMN status ENUM('pending','approved','rejected','returned','overdue','picked_up') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE dormitory_permits MODIFY COLUMN status ENUM('pending','approved','rejected','returned','overdue') NOT NULL DEFAULT 'pending'");
    }
};
