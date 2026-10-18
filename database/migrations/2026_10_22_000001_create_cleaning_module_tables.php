<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cleaning_areas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_unit_id')->nullable();
            $table->uuid('school_id')->nullable();
            $table->uuid('building_id')->nullable();
            $table->uuid('room_id')->nullable();
            $table->string('name');
            $table->string('area_type', 50)->default('lainnya');
            $table->text('location_notes')->nullable();
            $table->uuid('coordinator_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('work_unit_id')->references('id')->on('work_units')->nullOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('building_id')->references('id')->on('asset_buildings')->nullOnDelete();
            $table->foreign('room_id')->references('id')->on('asset_rooms')->nullOnDelete();
            $table->foreign('coordinator_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['work_unit_id', 'is_active']);
        });

        Schema::create('cleaning_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('area_id');
            $table->string('title');
            $table->text('standard')->nullable();
            $table->string('method', 60)->nullable();
            $table->string('frequency', 30)->default('harian');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('area_id')->references('id')->on('cleaning_areas')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['area_id', 'is_active', 'sort_order']);
        });

        Schema::create('cleaning_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('area_id');
            $table->uuid('user_id');
            $table->string('role', 30)->default('petugas');
            $table->uuid('backup_user_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('area_id')->references('id')->on('cleaning_areas')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('backup_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['area_id', 'user_id', 'start_date'], 'cleaning_assignment_unique');
            $table->index(['user_id', 'is_active']);
        });

        Schema::create('cleaning_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('area_id');
            $table->date('report_date');
            $table->uuid('officer_id');
            $table->uuid('substitute_for_user_id')->nullable();
            $table->text('absence_reason')->nullable();
            $table->string('overall_condition', 30)->default('bersih');
            $table->unsignedTinyInteger('score')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('submitted');
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('coordinator_note')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('area_id')->references('id')->on('cleaning_areas')->cascadeOnDelete();
            $table->foreign('officer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('substitute_for_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['area_id', 'report_date'], 'cleaning_report_area_date_unique');
            $table->index(['report_date', 'status']);
        });

        Schema::create('cleaning_report_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('report_id');
            $table->uuid('task_id');
            $table->string('result', 30)->default('sudah');
            $table->string('condition', 30)->nullable();
            $table->text('notes')->nullable();
            $table->string('before_photo_path')->nullable();
            $table->text('before_photo_url')->nullable();
            $table->string('after_photo_path')->nullable();
            $table->text('after_photo_url')->nullable();
            $table->timestamps();

            $table->foreign('report_id')->references('id')->on('cleaning_reports')->cascadeOnDelete();
            $table->foreign('task_id')->references('id')->on('cleaning_tasks')->cascadeOnDelete();
            $table->unique(['report_id', 'task_id']);
        });

        Schema::create('cleaning_programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->uuid('area_id')->nullable();
            $table->uuid('building_id')->nullable();
            $table->string('category', 50)->default('lainnya');
            $table->string('frequency', 30)->default('bulanan');
            $table->json('months')->nullable();
            $table->text('standard')->nullable();
            $table->string('method', 60)->nullable();
            $table->uuid('assigned_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('area_id')->references('id')->on('cleaning_areas')->nullOnDelete();
            $table->foreign('building_id')->references('id')->on('asset_buildings')->nullOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cleaning_programs');
        Schema::dropIfExists('cleaning_report_items');
        Schema::dropIfExists('cleaning_reports');
        Schema::dropIfExists('cleaning_assignments');
        Schema::dropIfExists('cleaning_tasks');
        Schema::dropIfExists('cleaning_areas');
    }
};
