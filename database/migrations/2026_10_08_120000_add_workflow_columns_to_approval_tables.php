<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menyelaraskan skema approval dengan model/service terbaru:
 * - approval_flows    : code, is_active
 * - approval_requests : approval_flow_id, requestable_type, requestable_id, current_step_id
 * - approval_actions  : user_id
 *
 * Additive & idempotent — tidak menghapus kolom lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_flows', function (Blueprint $table) {
            if (! Schema::hasColumn('approval_flows', 'code')) {
                $table->string('code')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('approval_flows', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }
        });

        Schema::table('approval_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('approval_requests', 'approval_flow_id')) {
                $table->char('approval_flow_id', 36)->nullable()->index()->after('requested_by');
            }
            if (! Schema::hasColumn('approval_requests', 'requestable_type')) {
                $table->string('requestable_type')->nullable()->after('approval_flow_id');
            }
            if (! Schema::hasColumn('approval_requests', 'requestable_id')) {
                $table->char('requestable_id', 36)->nullable()->after('requestable_type');
            }
            if (! Schema::hasColumn('approval_requests', 'current_step_id')) {
                $table->char('current_step_id', 36)->nullable()->after('requestable_id');
            }
        });

        Schema::table('approval_actions', function (Blueprint $table) {
            if (! Schema::hasColumn('approval_actions', 'user_id')) {
                $table->char('user_id', 36)->nullable()->index()->after('approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('approval_actions', function (Blueprint $table) {
            if (Schema::hasColumn('approval_actions', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });

        Schema::table('approval_requests', function (Blueprint $table) {
            foreach (['current_step_id', 'requestable_id', 'requestable_type', 'approval_flow_id'] as $column) {
                if (Schema::hasColumn('approval_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('approval_flows', function (Blueprint $table) {
            foreach (['code', 'is_active'] as $column) {
                if (Schema::hasColumn('approval_flows', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
