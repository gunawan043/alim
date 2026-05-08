<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sidebar_menus', function (Blueprint $table) {
            $table->json('route_params')->nullable()->after('route');
        });
    }

    public function down(): void
    {
        Schema::table('sidebar_menus', function (Blueprint $table) {
            $table->dropColumn('route_params');
        });
    }
};
