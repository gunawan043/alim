<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_maintenance_logs', function (Blueprint $table) {
            $table->decimal('operational_cost', 14, 2)->default(0)->after('cost');
        });
    }

    public function down(): void
    {
        Schema::table('asset_maintenance_logs', function (Blueprint $table) {
            $table->dropColumn('operational_cost');
        });
    }
};
