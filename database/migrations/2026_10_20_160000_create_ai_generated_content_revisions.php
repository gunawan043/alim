<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_generated_content_revisions')) {
            Schema::create('ai_generated_content_revisions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('ai_generated_content_id')->constrained('ai_generated_contents')->cascadeOnDelete();
                $table->json('content')->nullable();
                $table->uuid('changed_by')->nullable();
                $table->string('note')->nullable();
                $table->timestamps();

                $table->foreign('changed_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['ai_generated_content_id', 'created_at'], 'ai_revision_context_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generated_content_revisions');
    }
};
