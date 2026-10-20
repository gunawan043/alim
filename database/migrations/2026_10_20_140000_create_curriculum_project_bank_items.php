<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('curriculum_project_bank_items')) {
            Schema::create('curriculum_project_bank_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('school_id')->nullable()->constrained('schools')->cascadeOnDelete();
                $table->uuid('curriculum_profile_id')->nullable();
                $table->string('type', 10)->default('p5ra');
                $table->string('kind', 20)->default('theme')->comment('theme / activity');
                $table->string('title');
                $table->text('description')->nullable();
                $table->json('dimensions')->nullable();
                $table->boolean('is_active')->default(true);
                $table->uuid('created_by')->nullable();
                $table->timestamps();

                $table->foreign('curriculum_profile_id')->references('id')->on('curriculum_profiles')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['school_id', 'type', 'kind', 'is_active'], 'project_bank_context_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_project_bank_items');
    }
};
