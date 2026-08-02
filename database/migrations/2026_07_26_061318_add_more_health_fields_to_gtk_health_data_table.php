<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // No-op — all fields are already present in gtk_health_records from migration
        // 2026_07_26_061214_create_gtk_health_records_table
    }

    public function down(): void
    {
        // No-op — nothing to revert
    }
};
