<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_templates', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('code', 100)->unique();
            $table->string('name');
            $table->enum('workflow_type', ['maintenance', 'repair', 'audit', 'stock_opname', 'movement'])->index();
            $table->string('category_slug', 100)->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('checklist_template_items', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->foreignUuid('template_id')->constrained('checklist_templates')->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('label');
            $table->text('description')->nullable();
            $table->enum('response_type', ['boolean', 'text', 'number', 'choice', 'photo', 'severity'])->default('boolean');
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('triggers_failure')->default(false);
            $table->string('failure_severity', 20)->nullable();
            $table->timestamps();
            $table->index(['template_id', 'sequence']);
        });

        Schema::create('checklist_instances', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->foreignUuid('template_id')->constrained('checklist_templates')->cascadeOnDelete();
            $table->string('context_type', 100)->index();
            $table->string('context_id', 36)->index();
            $table->foreignUuid('executor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['in_progress', 'completed', 'failed', 'cancelled'])->default('in_progress');
            $table->unsignedInteger('failed_items_count')->default(0);
            $table->unsignedInteger('total_items_count')->default(0);
            $table->json('result_summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('checklist_instance_responses', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->foreignUuid('instance_id')->constrained('checklist_instances')->cascadeOnDelete();
            $table->foreignUuid('template_item_id')->constrained('checklist_template_items')->cascadeOnDelete();
            $table->json('response_value')->nullable();
            $table->boolean('passed')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_instance_responses');
        Schema::dropIfExists('checklist_instances');
        Schema::dropIfExists('checklist_template_items');
        Schema::dropIfExists('checklist_templates');
    }
};