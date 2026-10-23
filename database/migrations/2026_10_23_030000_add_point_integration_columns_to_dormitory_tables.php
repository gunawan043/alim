<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dormitory_violations')
            && ! Schema::hasColumn('dormitory_violations', 'point_catalog_id')) {
            Schema::table('dormitory_violations', function (Blueprint $table) {
                $table->uuid('point_catalog_id')->nullable()->after('points')->index();
                $table->uuid('point_rule_id')->nullable()->after('point_catalog_id')->index();
                $table->text('recommended_action')->nullable()->after('action_taken');
                $table->string('action_status', 20)->default('none')->after('recommended_action');
            });
        }

        if (Schema::hasTable('dormitory_rewards')
            && ! Schema::hasColumn('dormitory_rewards', 'point_catalog_id')) {
            Schema::table('dormitory_rewards', function (Blueprint $table) {
                $table->uuid('point_catalog_id')->nullable()->after('level')->index();
                $table->integer('points')->default(0)->after('point_catalog_id');
                $table->string('semester', 20)->nullable()->after('points');
                $table->timestamp('ledger_recorded_at')->nullable()->after('semester');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dormitory_violations')
            && Schema::hasColumn('dormitory_violations', 'point_catalog_id')) {
            Schema::table('dormitory_violations', function (Blueprint $table) {
                $table->dropIndex(['point_catalog_id']);
                $table->dropIndex(['point_rule_id']);
                $table->dropColumn(['point_catalog_id', 'point_rule_id', 'recommended_action', 'action_status']);
            });
        }

        if (Schema::hasTable('dormitory_rewards')
            && Schema::hasColumn('dormitory_rewards', 'point_catalog_id')) {
            Schema::table('dormitory_rewards', function (Blueprint $table) {
                $table->dropIndex(['point_catalog_id']);
                $table->dropColumn(['point_catalog_id', 'points', 'semester', 'ledger_recorded_at']);
            });
        }
    }
};
