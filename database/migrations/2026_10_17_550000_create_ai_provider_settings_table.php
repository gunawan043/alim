<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V9 — Pengaturan provider AI (multi-platform, OpenAI-compatible).
 *
 * Kunci API disimpan terenkripsi (cast encrypted pada model). Mendukung
 * provider berbayar maupun gratis/lokal (Ollama, LM Studio) — satu default aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_provider_settings')) {
            Schema::create('ai_provider_settings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('provider', 30)->default('custom')
                    ->comment('openai|gemini|groq|openrouter|anthropic|ollama|lmstudio|custom');
                $table->string('endpoint', 500);
                $table->text('api_key')->nullable()->comment('terenkripsi');
                $table->string('model', 150);
                $table->json('extra')->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_tested_at')->nullable();
                $table->string('last_test_status', 20)->nullable();
                $table->text('last_test_message')->nullable();
                $table->uuid('created_by')->nullable();
                $table->timestamps();

                $table->index(['is_active', 'is_default'], 'ai_provider_active_default_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_provider_settings');
    }
};
