<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_maintenance_schedules', function (Blueprint $table) {
            $table->enum('status', ['scheduled', 'assigned', 'in_progress', 'completed', 'cancelled'])
                ->default('scheduled')
                ->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('asset_maintenance_schedules', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
