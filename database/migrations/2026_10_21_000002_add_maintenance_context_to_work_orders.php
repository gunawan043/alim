<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->uuid('asset_id')->nullable()->change();
            $table->foreignId('repair_request_id')->nullable()->change();
            $table->uuid('maintenance_schedule_id')->nullable()->after('repair_request_id');
            $table->uuid('room_id')->nullable()->after('asset_id');
            $table->uuid('building_id')->nullable()->after('room_id');
            $table->uuid('created_by')->nullable()->after('assignee_id');

            $table->foreign('maintenance_schedule_id')->references('id')->on('asset_maintenance_schedules')->nullOnDelete();
            $table->foreign('room_id')->references('id')->on('asset_rooms')->nullOnDelete();
            $table->foreign('building_id')->references('id')->on('asset_buildings')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index('maintenance_schedule_id');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['building_id']);
            $table->dropForeign(['room_id']);
            $table->dropForeign(['maintenance_schedule_id']);
            $table->dropIndex(['maintenance_schedule_id']);
            $table->dropColumn(['maintenance_schedule_id', 'room_id', 'building_id', 'created_by']);
        });
    }
};
