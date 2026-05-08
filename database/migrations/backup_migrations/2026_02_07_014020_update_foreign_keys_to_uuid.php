<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $foreignKeyMappings = [
        'approval_actions' => [
            ['from' => 'approval_request_id', 'to' => 'approval_requests', 'ref' => 'id'],
            ['from' => 'approver_id', 'to' => 'users', 'ref' => 'id'],
            ['from' => 'step_id', 'to' => 'approval_flow_steps', 'ref' => 'id'],
        ],
        'approval_flow_steps' => [
            ['from' => 'flow_id', 'to' => 'approval_flows', 'ref' => 'id'],
            ['from' => 'role_id', 'to' => 'roles', 'ref' => 'id'],
            ['from' => 'user_id', 'to' => 'users', 'ref' => 'id'],
        ],
        'approval_flows' => [
            ['from' => 'created_by', 'to' => 'users', 'ref' => 'id'],
            ['from' => 'updated_by', 'to' => 'users', 'ref' => 'id'],
        ],
        'approval_requests' => [
            ['from' => 'flow_id', 'to' => 'approval_flows', 'ref' => 'id'],
            ['from' => 'requester_id', 'to' => 'users', 'ref' => 'id'],
            ['from' => 'approver_id', 'to' => 'users', 'ref' => 'id'],
            ['from' => 'model_id', 'to' => null, 'ref' => 'id'], // Polymorphic
        ],
        
        // Audit Logs
        'audit_logs' => [
            ['from' => 'user_id', 'to' => 'users', 'ref' => 'id'],
        ],
        
        // GTK Profiles & Related
        'gtk_profiles' => [
            ['from' => 'user_id', 'to' => 'users', 'ref' => 'id'],
            ['from' => 'work_unit_id', 'to' => 'work_units', 'ref' => 'id'],
        ],
        'gtk_addresses' => [
            ['from' => 'gtk_profile_id', 'to' => 'gtk_profiles', 'ref' => 'id'],
            ['from' => 'province_id', 'to' => 'indonesia_provinces', 'ref' => 'id'],
            ['from' => 'city_id', 'to' => 'indonesia_cities', 'ref' => 'id'],
            ['from' => 'district_id', 'to' => 'indonesia_districts', 'ref' => 'id'],
            ['from' => 'village_id', 'to' => 'indonesia_villages', 'ref' => 'id'],
        ],
        'gtk_contacts' => [
            ['from' => 'gtk_profile_id', 'to' => 'gtk_profiles', 'ref' => 'id'],
        ],
        'gtk_employments' => [
            ['from' => 'gtk_profile_id', 'to' => 'gtk_profiles', 'ref' => 'id'],
            ['from' => 'work_unit_id', 'to' => 'work_units', 'ref' => 'id'],
        ],
        'gtk_family_members' => [
            ['from' => 'gtk_profile_id', 'to' => 'gtk_profiles', 'ref' => 'id'],
        ],
        'gtk_career_paths' => [
            ['from' => 'gtk_profile_id', 'to' => 'gtk_profiles', 'ref' => 'id'],
            ['from' => 'work_unit_id', 'to' => 'work_units', 'ref' => 'id'],
        ],
        'gtk_competencies' => [
            ['from' => 'gtk_profile_id', 'to' => 'gtk_profiles', 'ref' => 'id'],
        ],
        'gtk_trainings' => [
            ['from' => 'gtk_profile_id', 'to' => 'gtk_profiles', 'ref' => 'id'],
        ],
        'gtk_additional_tasks' => [
            ['from' => 'gtk_profile_id', 'to' => 'gtk_profiles', 'ref' => 'id'],
        ],
        'gtk_recruitments' => [
            ['from' => 'gtk_profile_id', 'to' => 'gtk_profiles', 'ref' => 'id'],
            ['from' => 'work_unit_id', 'to' => 'work_units', 'ref' => 'id'],
        ],
        'gtk_requests' => [
            ['from' => 'gtk_profile_id', 'to' => 'gtk_profiles', 'ref' => 'id'],
            ['from' => 'approval_request_id', 'to' => 'approval_requests', 'ref' => 'id'],
        ],
        'gtk_transfer_requests' => [
            ['from' => 'gtk_profile_id', 'to' => 'gtk_profiles', 'ref' => 'id'],
            ['from' => 'from_work_unit_id', 'to' => 'work_units', 'ref' => 'id'],
            ['from' => 'to_work_unit_id', 'to' => 'work_units', 'ref' => 'id'],
            ['from' => 'approval_request_id', 'to' => 'approval_requests', 'ref' => 'id'],
        ],
        'gtk_work_unit' => [
            ['from' => 'gtk_profile_id', 'to' => 'gtk_profiles', 'ref' => 'id'],
            ['from' => 'work_unit_id', 'to' => 'work_units', 'ref' => 'id'],
        ],
        'gtk_work_unit_histories' => [
            ['from' => 'gtk_profile_id', 'to' => 'gtk_profiles', 'ref' => 'id'],
            ['from' => 'work_unit_id', 'to' => 'work_units', 'ref' => 'id'],
        ],
        
        // Password OTP & Secure Access
        'password_otps' => [
            ['from' => 'user_id', 'to' => 'users', 'ref' => 'id'],
        ],
        'secure_access_tokens' => [
            ['from' => 'user_id', 'to' => 'users', 'ref' => 'id'],
        ],
        
        // Indonesia Regions (self-referencing)
        'indonesia_cities' => [
            ['from' => 'province_id', 'to' => 'indonesia_provinces', 'ref' => 'id'],
        ],
        'indonesia_districts' => [
            ['from' => 'city_id', 'to' => 'indonesia_cities', 'ref' => 'id'],
        ],
        'indonesia_villages' => [
            ['from' => 'district_id', 'to' => 'indonesia_districts', 'ref' => 'id'],
        ],
        
        // Permission System
        'model_has_permissions' => [
            ['from' => 'permission_id', 'to' => 'permissions', 'ref' => 'id'],
        ],
        'model_has_roles' => [
            ['from' => 'role_id', 'to' => 'roles', 'ref' => 'id'],
        ],
        'role_has_permissions' => [
            ['from' => 'permission_id', 'to' => 'permissions', 'ref' => 'id'],
            ['from' => 'role_id', 'to' => 'roles', 'ref' => 'id'],
        ],
        
        // Personal Access Tokens
        'personal_access_tokens' => [
            ['from' => 'tokenable_id', 'to' => null, 'ref' => 'id'], // Polymorphic
        ],
    ];

    public function up()
    {
        foreach ($this->foreignKeyMappings as $tableName => $foreignKeys) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            
            foreach ($foreignKeys as $fk) {
                $column = $fk['from'];
                $referencedTable = $fk['to'];
                $referencedColumn = $fk['ref'];
                
                // Skip jika kolom tidak ada atau tabel referensi tidak ada
                if (!Schema::hasColumn($tableName, $column) || 
                    ($referencedTable && !Schema::hasTable($referencedTable))) {
                    continue;
                }
                
                // Untuk foreign key ke tabel dengan UUID
                if ($referencedTable && $this->shouldUseUuid($referencedTable)) {
                    $this->convertToUuidForeignKey($tableName, $column, $referencedTable);
                }
            }
        }
        
        // Handle polymorphic relations
        $this->handlePolymorphicRelations();
        
        // Add foreign key constraints
        $this->addForeignKeys();
    }
    
    private function shouldUseUuid($tableName)
    {
        $uuidTables = [
            'users', 'approval_flows', 'approval_flow_steps', 'approval_requests',
            'gtk_profiles', 'work_units', 'permissions', 'roles',
            'indonesia_provinces', 'indonesia_cities', 'indonesia_districts', 'indonesia_villages'
        ];
        
        return in_array($tableName, $uuidTables);
    }
    
    private function convertToUuidForeignKey($tableName, $column, $referencedTable)
    {
        Schema::table($tableName, function (Blueprint $blueprint) use ($tableName, $column, $referencedTable) {
            // Cek jika foreign key constraint ada
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '$tableName' 
                AND COLUMN_NAME = '$column'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            // Drop foreign key jika ada
            foreach ($foreignKeys as $fk) {
                $blueprint->dropForeign([$column]);
            }
            
            // Rename column ke _uuid
            $newColumnName = $column . '_uuid';
            if ($column !== 'tokenable_id' && !str_ends_with($column, '_uuid')) {
                $blueprint->renameColumn($column, $newColumnName);
            } else {
                $newColumnName = $column;
            }
            
            // Ubah tipe data ke UUID
            $blueprint->uuid($newColumnName)->nullable()->change();
        });
    }
    
    private function handlePolymorphicRelations()
    {
        // Untuk approval_requests.model_id (polymorphic)
        if (Schema::hasColumn('approval_requests', 'model_id')) {
            Schema::table('approval_requests', function (Blueprint $blueprint) {
                $blueprint->renameColumn('model_id', 'model_uuid');
                $blueprint->uuid('model_uuid')->nullable()->change();
            });
        }
        
        // Untuk personal_access_tokens.tokenable_id (polymorphic)
        if (Schema::hasColumn('personal_access_tokens', 'tokenable_id')) {
            Schema::table('personal_access_tokens', function (Blueprint $blueprint) {
                $blueprint->renameColumn('tokenable_id', 'tokenable_uuid');
                $blueprint->uuid('tokenable_uuid')->nullable()->change();
                $blueprint->string('tokenable_type')->change();
            });
        }
        
        // Untuk model_has_permissions dan model_has_roles
        // foreach (['model_has_permissions', 'model_has_roles'] as $pivotTable) {
        //     if (Schema::hasColumn($pivotTable, 'model_id')) {
        //         Schema::table($pivotTable, function (Blueprint $blueprint) use ($pivotTable) {
        //             // Tambah kolom model_uuid
        //             $blueprint->uuid('model_uuid')->nullable()->after('model_id');
                    
        //             // Copy data dari users.uuid ke model_uuid untuk user models
        //             if (Schema::hasTable('users')) {
        //                 DB::statement("
        //                     UPDATE $pivotTable mhp
        //                     JOIN users u ON mhp.model_id = u.id 
        //                     SET mhp.model_uuid = u.uuid
        //                     WHERE mhp.model_type = 'App\\\\Models\\\\User'
        //                 ");
        //             }
        //         });
        //     }
        // }
    }
    
    private function addForeignKeys()
    {
        // Add foreign key constraints setelah semua kolom diubah
        $constraints = [
            // Users references
            ['table' => 'audit_logs', 'column' => 'user_uuid', 'references' => 'users', 'refColumn' => 'uuid'],
            ['table' => 'password_otps', 'column' => 'user_uuid', 'references' => 'users', 'refColumn' => 'uuid'],
            ['table' => 'secure_access_tokens', 'column' => 'user_uuid', 'references' => 'users', 'refColumn' => 'uuid'],
            ['table' => 'gtk_profiles', 'column' => 'user_uuid', 'references' => 'users', 'refColumn' => 'uuid'],
            
            // Approval system
            ['table' => 'approval_actions', 'column' => 'approval_request_uuid', 'references' => 'approval_requests', 'refColumn' => 'uuid'],
            ['table' => 'approval_actions', 'column' => 'approver_uuid', 'references' => 'users', 'refColumn' => 'uuid'],
            ['table' => 'approval_actions', 'column' => 'step_uuid', 'references' => 'approval_flow_steps', 'refColumn' => 'uuid'],
            ['table' => 'approval_flow_steps', 'column' => 'flow_uuid', 'references' => 'approval_flows', 'refColumn' => 'uuid'],
            ['table' => 'approval_flow_steps', 'column' => 'role_uuid', 'references' => 'roles', 'refColumn' => 'uuid'],
            ['table' => 'approval_flow_steps', 'column' => 'user_uuid', 'references' => 'users', 'refColumn' => 'uuid'],
            ['table' => 'approval_flows', 'column' => 'created_by_uuid', 'references' => 'users', 'refColumn' => 'uuid'],
            ['table' => 'approval_flows', 'column' => 'updated_by_uuid', 'references' => 'users', 'refColumn' => 'uuid'],
            ['table' => 'approval_requests', 'column' => 'flow_uuid', 'references' => 'approval_flows', 'refColumn' => 'uuid'],
            ['table' => 'approval_requests', 'column' => 'requester_uuid', 'references' => 'users', 'refColumn' => 'uuid'],
            ['table' => 'approval_requests', 'column' => 'approver_uuid', 'references' => 'users', 'refColumn' => 'uuid'],
            
            // GTK system
            ['table' => 'gtk_addresses', 'column' => 'gtk_profile_uuid', 'references' => 'gtk_profiles', 'refColumn' => 'uuid'],
            ['table' => 'gtk_contacts', 'column' => 'gtk_profile_uuid', 'references' => 'gtk_profiles', 'refColumn' => 'uuid'],
            ['table' => 'gtk_employments', 'column' => 'gtk_profile_uuid', 'references' => 'gtk_profiles', 'refColumn' => 'uuid'],
            ['table' => 'gtk_employments', 'column' => 'work_unit_uuid', 'references' => 'work_units', 'refColumn' => 'uuid'],
            ['table' => 'gtk_family_members', 'column' => 'gtk_profile_uuid', 'references' => 'gtk_profiles', 'refColumn' => 'uuid'],
            ['table' => 'gtk_career_paths', 'column' => 'gtk_profile_uuid', 'references' => 'gtk_profiles', 'refColumn' => 'uuid'],
            ['table' => 'gtk_career_paths', 'column' => 'work_unit_uuid', 'references' => 'work_units', 'refColumn' => 'uuid'],
            ['table' => 'gtk_competencies', 'column' => 'gtk_profile_uuid', 'references' => 'gtk_profiles', 'refColumn' => 'uuid'],
            ['table' => 'gtk_trainings', 'column' => 'gtk_profile_uuid', 'references' => 'gtk_profiles', 'refColumn' => 'uuid'],
            ['table' => 'gtk_additional_tasks', 'column' => 'gtk_profile_uuid', 'references' => 'gtk_profiles', 'refColumn' => 'uuid'],
            ['table' => 'gtk_recruitments', 'column' => 'gtk_profile_uuid', 'references' => 'gtk_profiles', 'refColumn' => 'uuid'],
            ['table' => 'gtk_recruitments', 'column' => 'work_unit_uuid', 'references' => 'work_units', 'refColumn' => 'uuid'],
            ['table' => 'gtk_requests', 'column' => 'gtk_profile_uuid', 'references' => 'gtk_profiles', 'refColumn' => 'uuid'],
            ['table' => 'gtk_requests', 'column' => 'approval_request_uuid', 'references' => 'approval_requests', 'refColumn' => 'uuid'],
            ['table' => 'gtk_transfer_requests', 'column' => 'gtk_profile_uuid', 'references' => 'gtk_profiles', 'refColumn' => 'uuid'],
            ['table' => 'gtk_transfer_requests', 'column' => 'from_work_unit_uuid', 'references' => 'work_units', 'refColumn' => 'uuid'],
            ['table' => 'gtk_transfer_requests', 'column' => 'to_work_unit_uuid', 'references' => 'work_units', 'refColumn' => 'uuid'],
            ['table' => 'gtk_transfer_requests', 'column' => 'approval_request_uuid', 'references' => 'approval_requests', 'refColumn' => 'uuid'],
            ['table' => 'gtk_work_unit', 'column' => 'gtk_profile_uuid', 'references' => 'gtk_profiles', 'refColumn' => 'uuid'],
            ['table' => 'gtk_work_unit', 'column' => 'work_unit_uuid', 'references' => 'work_units', 'refColumn' => 'uuid'],
            ['table' => 'gtk_work_unit_histories', 'column' => 'gtk_profile_uuid', 'references' => 'gtk_profiles', 'refColumn' => 'uuid'],
            ['table' => 'gtk_work_unit_histories', 'column' => 'work_unit_uuid', 'references' => 'work_units', 'refColumn' => 'uuid'],
            
            // Indonesia regions
            ['table' => 'indonesia_cities', 'column' => 'province_uuid', 'references' => 'indonesia_provinces', 'refColumn' => 'uuid'],
            ['table' => 'indonesia_districts', 'column' => 'city_uuid', 'references' => 'indonesia_cities', 'refColumn' => 'uuid'],
            ['table' => 'indonesia_villages', 'column' => 'district_uuid', 'references' => 'indonesia_districts', 'refColumn' => 'uuid'],
            
            // Permission system
            // ['table' => 'model_has_permissions', 'column' => 'permission_uuid', 'references' => 'permissions', 'refColumn' => 'uuid'],
            // ['table' => 'model_has_roles', 'column' => 'role_uuid', 'references' => 'roles', 'refColumn' => 'uuid'],
            ['table' => 'role_has_permissions', 'column' => 'permission_uuid', 'references' => 'permissions', 'refColumn' => 'uuid'],
            ['table' => 'role_has_permissions', 'column' => 'role_uuid', 'references' => 'roles', 'refColumn' => 'uuid'],
            
            // GTK addresses references to indonesia regions
            ['table' => 'gtk_addresses', 'column' => 'province_uuid', 'references' => 'indonesia_provinces', 'refColumn' => 'uuid'],
            ['table' => 'gtk_addresses', 'column' => 'city_uuid', 'references' => 'indonesia_cities', 'refColumn' => 'uuid'],
            ['table' => 'gtk_addresses', 'column' => 'district_uuid', 'references' => 'indonesia_districts', 'refColumn' => 'uuid'],
            ['table' => 'gtk_addresses', 'column' => 'village_uuid', 'references' => 'indonesia_villages', 'refColumn' => 'uuid'],
        ];
        
        foreach ($constraints as $constraint) {
            if (Schema::hasColumn($constraint['table'], $constraint['column'])) {
                Schema::table($constraint['table'], function (Blueprint $blueprint) use ($constraint) {
                    $blueprint->foreign($constraint['column'])
                          ->references($constraint['refColumn'])
                          ->on($constraint['references'])
                          ->onDelete('cascade');
                });
            }
        }
    }

    private function updateForeignKeyData()
    {
        // Update foreign key values dari ID ke UUID
        $updateMappings = [
            // Update audit_logs.user_uuid
            [
                'table' => 'audit_logs',
                'uuid_column' => 'user_uuid',
                'ref_table' => 'users',
                'ref_id_column' => 'id',
                'ref_uuid_column' => 'uuid'
            ],
            // Update gtk_profiles.user_uuid
            [
                'table' => 'gtk_profiles',
                'uuid_column' => 'user_uuid',
                'ref_table' => 'users',
                'ref_id_column' => 'id',
                'ref_uuid_column' => 'uuid'
            ],
            // Update approval_requests.requester_uuid
            [
                'table' => 'approval_requests',
                'uuid_column' => 'requester_uuid',
                'ref_table' => 'users',
                'ref_id_column' => 'id',
                'ref_uuid_column' => 'uuid'
            ],
            // Tambahkan mapping lainnya sesuai kebutuhan
        ];
        
        foreach ($updateMappings as $mapping) {
            if (Schema::hasColumn($mapping['table'], $mapping['uuid_column']) &&
                Schema::hasColumn($mapping['ref_table'], $mapping['ref_uuid_column'])) {
                
                DB::statement("
                    UPDATE {$mapping['table']} t
                    JOIN {$mapping['ref_table']} r ON t.{$mapping['uuid_column']} = r.{$mapping['ref_id_column']}
                    SET t.{$mapping['uuid_column']} = r.{$mapping['ref_uuid_column']}
                    WHERE t.{$mapping['uuid_column']} IS NOT NULL 
                    AND t.{$mapping['uuid_column']} REGEXP '^[0-9]+$'
                ");
            }
        }
    }

    public function down()
    {
        // Drop semua foreign key constraints yang baru
        $constraints = [
            'audit_logs' => ['user_uuid'],
            'password_otps' => ['user_uuid'],
            'secure_access_tokens' => ['user_uuid'],
            'gtk_profiles' => ['user_uuid', 'work_unit_uuid'],
            'approval_actions' => ['approval_request_uuid', 'approver_uuid', 'step_uuid'],
            'approval_flow_steps' => ['flow_uuid', 'role_uuid', 'user_uuid'],
            'approval_flows' => ['created_by_uuid', 'updated_by_uuid'],
            'approval_requests' => ['flow_uuid', 'requester_uuid', 'approver_uuid'],
            'gtk_addresses' => ['gtk_profile_uuid', 'province_uuid', 'city_uuid', 'district_uuid', 'village_uuid'],
            'gtk_contacts' => ['gtk_profile_uuid'],
            'gtk_employments' => ['gtk_profile_uuid', 'work_unit_uuid'],
            'gtk_family_members' => ['gtk_profile_uuid'],
            'gtk_career_paths' => ['gtk_profile_uuid', 'work_unit_uuid'],
            'gtk_competencies' => ['gtk_profile_uuid'],
            'gtk_trainings' => ['gtk_profile_uuid'],
            'gtk_additional_tasks' => ['gtk_profile_uuid'],
            'gtk_recruitments' => ['gtk_profile_uuid', 'work_unit_uuid'],
            'gtk_requests' => ['gtk_profile_uuid', 'approval_request_uuid'],
            'gtk_transfer_requests' => ['gtk_profile_uuid', 'from_work_unit_uuid', 'to_work_unit_uuid', 'approval_request_uuid'],
            'gtk_work_unit' => ['gtk_profile_uuid', 'work_unit_uuid'],
            'gtk_work_unit_histories' => ['gtk_profile_uuid', 'work_unit_uuid'],
            'indonesia_cities' => ['province_uuid'],
            'indonesia_districts' => ['city_uuid'],
            'indonesia_villages' => ['district_uuid'],
            'model_has_permissions' => ['permission_uuid'],
            'model_has_roles' => ['role_uuid'],
            'role_has_permissions' => ['permission_uuid', 'role_uuid'],
        ];
        
        foreach ($constraints as $table => $columns) {
            foreach ($columns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    Schema::table($table, function (Blueprint $blueprint) use ($column) {
                        try {
                            $blueprint->dropForeign([$column]);
                        } catch (\Exception $e) {
                            // Skip jika constraint tidak ada
                        }
                    });
                }
            }
        }
        
        // Revert column names dan tipe data untuk semua tabel
        $revertMappings = [
            'audit_logs' => ['user_uuid'],
            'password_otps' => ['user_uuid'],
            'secure_access_tokens' => ['user_uuid'],
            'gtk_profiles' => ['user_uuid', 'work_unit_uuid'],
            'approval_actions' => ['approval_request_uuid', 'approver_uuid', 'step_uuid'],
            'approval_flow_steps' => ['flow_uuid', 'role_uuid', 'user_uuid'],
            'approval_flows' => ['created_by_uuid', 'updated_by_uuid'],
            'approval_requests' => ['flow_uuid', 'requester_uuid', 'approver_uuid'],
            'gtk_addresses' => ['gtk_profile_uuid', 'province_uuid', 'city_uuid', 'district_uuid', 'village_uuid'],
            'gtk_contacts' => ['gtk_profile_uuid'],
            'gtk_employments' => ['gtk_profile_uuid', 'work_unit_uuid'],
            'gtk_family_members' => ['gtk_profile_uuid'],
            'gtk_career_paths' => ['gtk_profile_uuid', 'work_unit_uuid'],
            'gtk_competencies' => ['gtk_profile_uuid'],
            'gtk_trainings' => ['gtk_profile_uuid'],
            'gtk_additional_tasks' => ['gtk_profile_uuid'],
            'gtk_recruitments' => ['gtk_profile_uuid', 'work_unit_uuid'],
            'gtk_requests' => ['gtk_profile_uuid', 'approval_request_uuid'],
            'gtk_transfer_requests' => ['gtk_profile_uuid', 'from_work_unit_uuid', 'to_work_unit_uuid', 'approval_request_uuid'],
            'gtk_work_unit' => ['gtk_profile_uuid', 'work_unit_uuid'],
            'gtk_work_unit_histories' => ['gtk_profile_uuid', 'work_unit_uuid'],
            'indonesia_cities' => ['province_uuid'],
            'indonesia_districts' => ['city_uuid'],
            'indonesia_villages' => ['district_uuid'],
            'model_has_permissions' => ['permission_uuid'],
            'model_has_roles' => ['role_uuid'],
            'role_has_permissions' => ['permission_uuid', 'role_uuid'],
        ];
        
        foreach ($revertMappings as $table => $columns) {
            foreach ($columns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    Schema::table($table, function (Blueprint $blueprint) use ($column) {
                        $oldName = str_replace('_uuid', '_id', $column);
                        $blueprint->renameColumn($column, $oldName);
                        
                        // Tentukan tipe data berdasarkan nama kolom
                        if (str_contains($oldName, 'id')) {
                            $blueprint->unsignedBigInteger($oldName)->change();
                        }
                    });
                }
            }
        }
        
        // Revert polymorphic
        if (Schema::hasColumn('approval_requests', 'model_uuid')) {
            Schema::table('approval_requests', function (Blueprint $blueprint) {
                $blueprint->renameColumn('model_uuid', 'model_id');
                $blueprint->unsignedBigInteger('model_id')->change();
            });
        }
        
        if (Schema::hasColumn('personal_access_tokens', 'tokenable_uuid')) {
            Schema::table('personal_access_tokens', function (Blueprint $blueprint) {
                $blueprint->renameColumn('tokenable_uuid', 'tokenable_id');
                $blueprint->unsignedBigInteger('tokenable_id')->change();
            });
        }
        
        // Revert model_has_* tables
        foreach (['model_has_permissions', 'model_has_roles'] as $table) {
            if (Schema::hasColumn($table, 'model_uuid')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('model_uuid');
                });
            }
        }
    }
};