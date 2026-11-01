<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dormitory_daily_tasks')) {
            Schema::create('dormitory_daily_tasks', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('dormitory_id')->constrained('dormitories')->cascadeOnDelete();
                $table->foreignUuid('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
                $table->string('shift', 20)->default('pagi');
                $table->string('name', 191);
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['dormitory_id', 'shift', 'is_active', 'sort_order'], 'ddt_dorm_shift_active_idx');
            });
        }

        if (! Schema::hasTable('dormitory_daily_task_logs')) {
            Schema::create('dormitory_daily_task_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('task_id')->nullable()->constrained('dormitory_daily_tasks')->nullOnDelete();
                $table->foreignUuid('dormitory_id')->constrained('dormitories')->cascadeOnDelete();
                $table->foreignUuid('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
                $table->date('task_date');
                $table->string('shift', 20);
                $table->string('task_name', 191);
                $table->boolean('is_done')->default(false);
                $table->text('notes')->nullable();
                $table->foreignUuid('checked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('checked_at')->nullable();
                $table->timestamps();

                $table->unique(['task_id', 'task_date'], 'ddtl_task_date_unique');
                $table->index(['dormitory_id', 'task_date', 'shift'], 'ddtl_dorm_date_shift_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_daily_task_logs');
        Schema::dropIfExists('dormitory_daily_tasks');
    }
};
