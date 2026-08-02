<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL-only: SQLite does not support MODIFY COLUMN
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('activity_log')) {
            DB::statement('ALTER TABLE activity_log MODIFY subject_id CHAR(36) NULL');
            DB::statement('ALTER TABLE activity_log MODIFY causer_id CHAR(36) NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('activity_log')) {
            DB::statement('ALTER TABLE activity_log MODIFY subject_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE activity_log MODIFY causer_id BIGINT UNSIGNED NULL');
        }
    }
};
