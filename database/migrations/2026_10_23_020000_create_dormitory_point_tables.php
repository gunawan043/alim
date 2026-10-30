<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dormitory_point_catalogs')) {
        Schema::create('dormitory_point_catalogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kind', 20); // violation|reward
            $table->string('code', 30)->unique();
            $table->string('name', 191);
            $table->string('category', 80);
            $table->string('severity', 30)->nullable();
            $table->integer('points_min')->default(0);
            $table->integer('points_max')->nullable();
            $table->boolean('is_permanent')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['kind', 'is_active', 'sort_order']);
        });
        }

        if (! Schema::hasTable('dormitory_point_rules')) {
        Schema::create('dormitory_point_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->integer('min_points');
            $table->integer('max_points')->nullable();
            $table->string('action');
            $table->boolean('requires_approval')->default(false);
            $table->json('notify_roles')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        }

        if (! Schema::hasTable('dormitory_point_ledger')) {
        Schema::create('dormitory_point_ledger', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id')->index();
            $table->uuid('dormitory_id')->index();
            $table->uuid('academic_year_id')->nullable()->index();
            $table->string('semester', 20)->nullable();
            $table->string('kind', 20); // violation|reward|adjustment
            $table->integer('points'); // positive reward, negative violation
            $table->boolean('is_permanent')->default(false);
            $table->string('reference_type')->nullable();
            $table->uuid('reference_id')->nullable();
            $table->text('note')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'academic_year_id', 'semester'], 'dorm_point_ledger_student_year_sem_idx');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_point_ledger');
        Schema::dropIfExists('dormitory_point_rules');
        Schema::dropIfExists('dormitory_point_catalogs');
    }
};
