<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // No-op — all these columns were already added by create_uks_patients_table
        // (2026_07_31_120900)
    }

    public function down(): void
    {
        // No-op — nothing to revert
    }
};
