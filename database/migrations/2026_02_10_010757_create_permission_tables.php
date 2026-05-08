<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        
        // PERMISSIONS
        Schema::create($tableNames['permissions'], function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('guard_name');
            $table->string('description')->nullable();
            $table->string('group')->nullable();
            $table->timestamps();
            
            $table->unique(['name', 'guard_name']);
        });

        // ROLES
        Schema::create($tableNames['roles'], function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('guard_name');
            $table->unsignedTinyInteger('level')->default(0);
            $table->string('description')->nullable();
            $table->timestamps();
            
            $table->unique(['name', 'guard_name']);
        });

        // MODEL HAS PERMISSIONS
        Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames) {
            $table->uuid('permission_id');
            $table->string('model_type');
            $table->uuid('model_id');
            
            $table->index(['model_id', 'model_type']);
            
            $table->foreign('permission_id')
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');
                
            $table->primary(
                ['permission_id', 'model_id', 'model_type'],
                'model_has_permissions_permission_model_type_primary'
            );
        });

        // MODEL HAS ROLES
        Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames) {
            $table->uuid('role_id');
            $table->string('model_type');
            $table->uuid('model_id');
            
            $table->index(['model_id', 'model_type']);
            
            $table->foreign('role_id')
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');
                
            $table->primary(
                ['role_id', 'model_id', 'model_type'],
                'model_has_roles_role_model_type_primary'
            );
        });

        // ROLE HAS PERMISSIONS
        Schema::create($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames) {
            $table->uuid('permission_id');
            $table->uuid('role_id');
            
            $table->foreign('permission_id')
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');
                
            $table->foreign('role_id')
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');
                
            $table->primary(['permission_id', 'role_id']);
        });
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        
        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['roles']);
        Schema::dropIfExists($tableNames['permissions']);
    }
};