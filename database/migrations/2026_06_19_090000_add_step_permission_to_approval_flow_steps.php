<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_flow_steps', function (Blueprint $table) {
            $table->string('step_permission')->nullable()->after('role_name');
        });

        // Backfill existing rows from ApprovalRoleResolver registry
        \App\Models\ApprovalFlowStep::query()
            ->whereNull('step_permission')
            ->orderBy('approval_flow_id')
            ->orderBy('step_order')
            ->chunk(100, function ($steps) {
                foreach ($steps as $step) {
                    $permissions = \App\Authorization\Services\ApprovalRoleResolver::resolvePermission($step->role_name);
                    $step->step_permission = $permissions[0] ?? $step->role_name;
                    $step->save();
                }
            });
    }

    public function down(): void
    {
        Schema::table('approval_flow_steps', function (Blueprint $table) {
            $table->dropColumn('step_permission');
        });
    }
};