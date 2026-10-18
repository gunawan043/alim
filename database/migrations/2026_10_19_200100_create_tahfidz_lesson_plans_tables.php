<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tahfidz_lesson_plans')) {
            Schema::create('tahfidz_lesson_plans', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('work_unit_id')->index();
                $table->uuid('school_id')->nullable()->index();
                $table->uuid('grade_level_id')->nullable()->index();
                $table->uuid('academic_year_id')->index();
                $table->string('semester', 10);
                $table->string('type', 20)->default('tahfizh')->index();
                $table->string('title', 150);
                $table->string('kbm_month', 30)->nullable();
                $table->text('monthly_target')->nullable();
                $table->text('notes')->nullable();
                $table->string('status', 20)->default('draft')->index();
                $table->uuid('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tahfidz_lesson_plan_items')) {
            Schema::create('tahfidz_lesson_plan_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('plan_id')->index();
                $table->unsignedTinyInteger('week')->nullable();
                $table->string('section', 30)->nullable();
                $table->string('learning_day', 30)->nullable();
                $table->string('week_target', 255)->nullable();
                $table->text('daily_target')->nullable();
                $table->text('teacher_activity')->nullable();
                $table->text('student_activity')->nullable();
                $table->unsignedSmallInteger('duration_minutes')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedTinyInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_lesson_plan_items');
        Schema::dropIfExists('tahfidz_lesson_plans');
    }
};
