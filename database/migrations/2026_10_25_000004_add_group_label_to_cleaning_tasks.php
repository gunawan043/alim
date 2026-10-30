<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The original add_group_label migration runs before the cleaning module
     * table is created, so fresh installs could miss the column. Add it here
     * for databases that already created cleaning_tasks without it.
     */
    public function up(): void
    {
        if (Schema::hasTable('cleaning_tasks') && ! Schema::hasColumn('cleaning_tasks', 'group_label')) {
            Schema::table('cleaning_tasks', function (Blueprint $table) {
                $table->string('group_label', 100)->nullable()->after('area_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cleaning_tasks') && Schema::hasColumn('cleaning_tasks', 'group_label')) {
            Schema::table('cleaning_tasks', function (Blueprint $table) {
                $table->dropColumn('group_label');
            });
        }
    }
};
