<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('sync_token', 100)->nullable()->after('id');
            $table->index('sync_token');
        });

        Schema::table('asset_movements', function (Blueprint $table) {
            $table->string('sync_token', 100)->nullable()->after('id');
            $table->index('sync_token');
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('sync_token', 100)->nullable()->after('id');
            $table->softDeletes();
            $table->index('sync_token');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex(['sync_token']);
            $table->dropColumn('sync_token');
        });

        Schema::table('asset_movements', function (Blueprint $table) {
            $table->dropIndex(['sync_token']);
            $table->dropColumn('sync_token');
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropIndex(['sync_token']);
            $table->dropSoftDeletes();
            $table->dropColumn('sync_token');
        });
    }
};
