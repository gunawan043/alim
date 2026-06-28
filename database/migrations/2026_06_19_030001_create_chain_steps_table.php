<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chain_steps', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('chain_id', 36);
            $table->string('event_name', 120);
            $table->string('job_class', 255)->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'skipped', 'stale'])
                ->default('pending');
            $table->unsignedSmallInteger('step_order')->default(0);
            $table->json('payload_snapshot')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('retry_count')->default(0);
            $table->timestamp('triggered_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index('chain_id', 'chain_steps_chain_id_idx');
            $table->index('event_name', 'chain_steps_event_name_idx');
            $table->unique(['chain_id', 'event_name'], 'chain_steps_chain_event_unique');
            $table->index(['status', 'triggered_at'], 'chain_steps_status_triggered_idx');
            $table->index(['status', 'updated_at'], 'chain_steps_status_updated_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chain_steps');
    }
};
