<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * This file previously created gtk_health_records which already existed.
     * The old migration (2026_07_26_061214) covers it. No-op here.
     */
    public function up(): void
    {
        // Table already exists via 2026_07_26_061214_create_gtk_health_records_table
    }

    public function down(): void
    {
        // Nothing — the original table was created by 2026_07_26_061214
    }
};
