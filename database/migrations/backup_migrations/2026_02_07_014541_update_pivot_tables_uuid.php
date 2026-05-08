<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Update model_has_permissions
        Schema::table('model_has_permissions', function (Blueprint $table) {
            // Tambah kolom model_uuid untuk relasi ke user via UUID
            $table->uuid('model_uuid')->nullable()->after('model_id');
        });
        
        // Update model_has_roles
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->uuid('model_uuid')->nullable()->after('model_id');
        });
        
        // Copy UUID dari users table ke pivot tables
        $this->copyUuidToPivotTables();
    }
    
    private function copyUuidToPivotTables()
    {
        // Copy UUID untuk model_has_permissions
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'uuid')) {
            // Untuk model_has_permissions
            if (Schema::hasColumn('model_has_permissions', 'model_uuid')) {
                DB::statement("
                    UPDATE model_has_permissions mhp
                    JOIN users u ON mhp.model_id = u.id 
                    SET mhp.model_uuid = u.uuid
                    WHERE mhp.model_type = 'App\\\\Models\\\\User'
                    AND mhp.model_uuid IS NULL
                ");
            }
            
            // Untuk model_has_roles
            if (Schema::hasColumn('model_has_roles', 'model_uuid')) {
                DB::statement("
                    UPDATE model_has_roles mhr
                    JOIN users u ON mhr.model_id = u.id 
                    SET mhr.model_uuid = u.uuid
                    WHERE mhr.model_type = 'App\\\\Models\\\\User'
                    AND mhr.model_uuid IS NULL
                ");
            }
            
            // Untuk model_type lainnya (jika ada)
            $this->copyUuidForOtherModels();
        }
    }
    
    private function copyUuidForOtherModels()
    {
        // Daftar model lain yang mungkin menggunakan UUID
        $otherModels = [
            'App\\Models\\GtkProfile' => 'gtk_profiles',
            // Tambahkan model lainnya jika diperlukan
        ];
        
        foreach ($otherModels as $modelClass => $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'uuid')) {
                // Untuk model_has_permissions
                if (Schema::hasColumn('model_has_permissions', 'model_uuid')) {
                    DB::statement("
                        UPDATE model_has_permissions mhp
                        JOIN $tableName t ON mhp.model_id = t.id 
                        SET mhp.model_uuid = t.uuid
                        WHERE mhp.model_type = '$modelClass'
                        AND mhp.model_uuid IS NULL
                    ");
                }
                
                // Untuk model_has_roles
                if (Schema::hasColumn('model_has_roles', 'model_uuid')) {
                    DB::statement("
                        UPDATE model_has_roles mhr
                        JOIN $tableName t ON mhr.model_id = t.id 
                        SET mhr.model_uuid = t.uuid
                        WHERE mhr.model_type = '$modelClass'
                        AND mhr.model_uuid IS NULL
                    ");
                }
            }
        }
    }

    public function down()
    {
        // Hapus kolom model_uuid
        Schema::table('model_has_permissions', function (Blueprint $table) {
            if (Schema::hasColumn('model_has_permissions', 'model_uuid')) {
                $table->dropColumn('model_uuid');
            }
        });
        
        Schema::table('model_has_roles', function (Blueprint $table) {
            if (Schema::hasColumn('model_has_roles', 'model_uuid')) {
                $table->dropColumn('model_uuid');
            }
        });
    }
};