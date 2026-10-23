<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('additional_task_type_work_unit_hours')) {
            return;
        }

        Schema::create('additional_task_type_work_unit_hours', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_unit_id');
            $table->uuid('additional_task_type_id');
            $table->decimal('weekly_hours', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['work_unit_id', 'additional_task_type_id'], 'task_type_work_unit_unique');
            $table->foreign('work_unit_id')->references('id')->on('work_units')->cascadeOnDelete();
            $table->foreign('additional_task_type_id')->references('id')->on('additional_task_types')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('additional_task_type_work_unit_hours');
    }
};
