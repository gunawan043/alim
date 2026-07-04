<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_actions', function (Blueprint $table) {
            $table->string('step_permission')->nullable()->after('role_name');
        });

        // Backfill existing actions from the role_name registry
        \App\Models\ApprovalAction::query()
            ->whereNull('step_permission')
            ->chunk(100, function ($actions) {
                foreach ($actions as $action) {
                    $permissions = \App\Authorization\Services\ApprovalRoleResolver::resolvePermission($action->role_name);
                    $action->step_permission = $permissions[0] ?? $action->role_name;
                    $action->save();
                }
            });
    }

    public function down(): void
    {
        Schema::table('approval_actions', function (Blueprint $table) {
            $table->dropColumn('step_permission');
        });
    }
};