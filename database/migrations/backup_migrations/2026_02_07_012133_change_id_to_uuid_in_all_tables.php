<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    protected $tables = [
        'users',
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
        'work_units',
        'password_otps',
        'secure_access_tokens',
    ];

    protected $excludeTables = [
        'migrations',
        'password_resets',
        'sessions',
        'personal_access_tokens',
        'model_has_permissions',
        'model_has_roles',
        'role_has_permissions',
        'indonesia_provinces',
        'indonesia_cities',
        'indonesia_districts',
        'indonesia_villages',
        'permissions',
        'roles',
    ];

    public function up()
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName)) {

                if (! Schema::hasColumn($tableName, 'uuid')) {
                    Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                        $table->uuid('uuid')->nullable()->after('id');

                        // Tambah unique constraint
                        if (! Schema::hasColumn($tableName, 'deleted_at')) {
                            $table->unique('uuid');
                        } else {
                            $table->unique(['uuid', 'deleted_at']);
                        }
                    });

                    $this->generateUuidForExistingData($tableName);
                }
            }
        }

        $this->createUuidForeignKeys();
    }

    private function generateUuidForExistingData($tableName)
    {
        DB::table($tableName)
            ->whereNull('uuid')
            ->orderBy('id')
            ->chunk(100, function ($records) use ($tableName) {
                foreach ($records as $record) {
                    DB::table($tableName)
                        ->where('id', $record->id)
                        ->update(['uuid' => (string) Str::uuid()]);
                }
            });
    }

    private function createUuidForeignKeys()
    {
        $foreignKeyUpdates = [
            // Tabel yang reference ke users
            'audit_logs' => [
                ['column' => 'user_id', 'references' => 'users', 'on' => 'id'],
            ],
            'approval_requests' => [
                ['column' => 'requester_id', 'references' => 'users', 'on' => 'id'],
                ['column' => 'approver_id', 'references' => 'users', 'on' => 'id'],
            ],
        ];

        foreach ($foreignKeyUpdates as $table => $relations) {
            foreach ($relations as $relation) {
                if (Schema::hasColumn($table, $relation['column'])) {
                    Schema::table($table, function (Blueprint $table) use ($relation) {
                        $table->dropForeign([$relation['column']]);

                        $table->index([$relation['column']]);
                    });
                }
            }
        }
    }

    public function down()
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'uuid')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropUnique(['uuid']);

                    $table->dropColumn('uuid');
                });
            }
        }
    }
};
