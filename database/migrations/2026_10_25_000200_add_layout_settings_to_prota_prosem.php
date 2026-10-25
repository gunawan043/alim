<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['prota', 'prosem'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'layout_settings')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->json('layout_settings')->nullable()->after('catatan');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['prota', 'prosem'] as $tableName) {
            if (Schema::hasColumn($tableName, 'layout_settings')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('layout_settings');
                });
            }
        }
    }
};
