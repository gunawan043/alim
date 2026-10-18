<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cleaning_assignments') && ! Schema::hasColumn('cleaning_assignments', 'supervisor_id')) {
            Schema::table('cleaning_assignments', function (Blueprint $table) {
                $table->uuid('supervisor_id')->nullable()->after('backup_user_id');
                $table->foreign('supervisor_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cleaning_assignments') && Schema::hasColumn('cleaning_assignments', 'supervisor_id')) {
            Schema::table('cleaning_assignments', function (Blueprint $table) {
                $table->dropForeign(['supervisor_id']);
                $table->dropColumn('supervisor_id');
            });
        }
    }
};
