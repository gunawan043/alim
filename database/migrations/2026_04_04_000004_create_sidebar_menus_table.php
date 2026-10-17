<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sidebar_menus')) {
            Schema::create('sidebar_menus', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('parent_id')->nullable();
                $table->string('name');
                $table->string('icon')->nullable();
                $table->string('route')->nullable();
                $table->string('url')->nullable();
                $table->integer('order')->default(0);
                $table->boolean('is_group_header')->default(false);
                $table->boolean('is_active')->default(true);
                $table->string('guard_name')->default('web');
                $table->timestamps();

                $table->foreign('parent_id')->references('id')->on('sidebar_menus')->onDelete('cascade');
                $table->index(['parent_id', 'order']);
                $table->index(['is_active', 'order']);
            });
        }

        if (! Schema::hasTable('sidebar_menu_role')) {
            Schema::create('sidebar_menu_role', function (Blueprint $table) {
                $table->uuid('sidebar_menu_id');
                $table->uuid('role_id');
                $table->primary(['sidebar_menu_id', 'role_id']);

                $table->foreign('sidebar_menu_id')->references('id')->on('sidebar_menus')->onDelete('cascade');
                if (Schema::hasTable('roles')) {
                    $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sidebar_menu_role');
        Schema::dropIfExists('sidebar_menus');
    }
};
