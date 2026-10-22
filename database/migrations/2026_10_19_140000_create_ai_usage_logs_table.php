<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->nullable()->constrained('ai_chat_conversations')->nullOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->string('provider', 100)->nullable();
            $table->string('model', 150)->nullable();
            $table->string('quality', 20)->default('flash');
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->decimal('cost', 12, 8)->default(0);
            $table->json('tools')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'ai_usage_user_time_idx');
            $table->index(['school_id', 'created_at'], 'ai_usage_school_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
