<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('perangkat_pembelajaran', 'layout_settings')) {
            Schema::table('perangkat_pembelajaran', function (Blueprint $table) {
                $table->json('layout_settings')->nullable()->after('desain');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('perangkat_pembelajaran', 'layout_settings')) {
            Schema::table('perangkat_pembelajaran', function (Blueprint $table) {
                $table->dropColumn('layout_settings');
            });
        }
    }
};
