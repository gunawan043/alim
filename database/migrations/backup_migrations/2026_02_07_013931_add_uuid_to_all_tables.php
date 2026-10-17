<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    protected $allTables = [
        'approval_actions',
        'approval_flows',
        'approval_flow_steps',
        'approval_requests',
        'audit_logs',
        'failed_jobs',
        'gtk_additional_tasks',
        'gtk_addresses',
        'gtk_career_paths',
        'gtk_competencies',
        'gtk_contacts',
        'gtk_employments',
        'gtk_family_members',
        'gtk_profiles',
        'gtk_recruitments',
        'gtk_requests',
        'gtk_trainings',
        'gtk_transfer_requests',
        'gtk_work_unit',
        'gtk_work_unit_histories',
        'indonesia_cities',
        'indonesia_districts',
        'indonesia_provinces',
        'indonesia_villages',
        'migrations',
        'model_has_permissions',
        'model_has_roles',
        'password_otps',
        'password_resets',
        'permissions',
        'personal_access_tokens',
        'roles',
        'role_has_permissions',
        'secure_access_tokens',
        'sessions',
        'users',
        'work_units',
    ];

    protected $tablesWithUuid = [
        'approval_actions',
        'approval_flows',
        'approval_flow_steps',
        'approval_requests',
        'audit_logs',
        'failed_jobs',
        'gtk_additional_tasks',
        'gtk_addresses',
        'gtk_career_paths',
        'gtk_competencies',
        'gtk_contacts',
        'gtk_employments',
        'gtk_family_members',
        'gtk_profiles',
        'gtk_recruitments',
        'gtk_requests',
        'gtk_trainings',
        'gtk_transfer_requests',
        'gtk_work_unit',
        'gtk_work_unit_histories',
        'password_otps',
        'permissions',
        'roles',
        'secure_access_tokens',
        'users',
        'work_units',
    ];

    public function up()
    {
        foreach ($this->tablesWithUuid as $tableName) {
            if (Schema::hasTable($tableName)) {
                if (! Schema::hasColumn($tableName, 'uuid')) {
                    Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                        $table->uuid('uuid')->nullable()->after('id');

                        $table->index('uuid');

                        if (Schema::hasColumn($tableName, 'deleted_at')) {
                            $table->unique(['uuid', 'deleted_at']);
                        } else {
                            $table->unique('uuid');
                        }
                    });

                    $this->generateUuidForExistingData($tableName);

                    Schema::table($tableName, function (Blueprint $table) {
                        $table->uuid('uuid')->nullable(false)->change();
                    });
                }
            }
        }
    }

    private function generateUuidForExistingData($tableName)
    {
        DB::table($tableName)
            ->whereNull('uuid')
            ->orderBy('id')
            ->chunk(500, function ($records) use ($tableName) {
                $updates = [];
                $now = now();

                foreach ($records as $record) {
                    $updates[] = [
                        'id' => $record->id,
                        'uuid' => (string) Str::uuid(),
                    ];
                }

                // Batch update untuk performa
                foreach ($updates as $update) {
                    DB::table($tableName)
                        ->where('id', $update['id'])
                        ->update(['uuid' => $update['uuid']]);
                }
            });
    }

    public function down()
    {
        foreach ($this->tablesWithUuid as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'uuid')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'deleted_at')) {
                        $table->dropUnique(['uuid', 'deleted_at']);
                    } else {
                        $table->dropUnique(['uuid']);
                    }

                    $table->dropIndex(['uuid']);
                    $table->dropColumn('uuid');
                });
            }
        }
    }
};
