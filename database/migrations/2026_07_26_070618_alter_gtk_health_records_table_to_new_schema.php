<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // gtk_health_records already exists with a comprehensive schema.
        // This migration was originally intended to create a new table, but since the
        // old table covers all needed health data fields, we keep it as-is.
        // New related tables (vaccinations, medical_histories) are created separately.
    }

    public function down(): void
    {
        // No-op — nothing to revert
    }
};
