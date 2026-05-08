<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Only run if role_id is still bigint (not yet converted to uuid/char)
        $columnType = DB::selectOne(
            "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_NAME = 'sidebar_menu_role' AND COLUMN_NAME = 'role_id'"
        );

        if (!$columnType || $columnType->COLUMN_TYPE === 'char(36)') {
            return; // Already fixed
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('sidebar_menu_role', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
        });

        Schema::table('sidebar_menu_role', function (Blueprint $table) {
            $table->string('role_id', 36)->change();
        });

        Schema::table('sidebar_menu_role', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // No-op: we don't revert the uuid change
    }
};
