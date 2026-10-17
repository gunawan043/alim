<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chains', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('root_event', 120);
            $table->string('aggregate_type', 80);
            $table->char('aggregate_id', 36);
            $table->char('school_id', 36)->nullable();
            $table->char('academic_year_id', 36)->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])
                ->default('pending');
            $table->json('context')->nullable();
            $table->unsignedSmallInteger('total_steps')->default(0);
            $table->unsignedSmallInteger('completed_steps')->default(0);
            $table->unsignedSmallInteger('failed_steps')->default(0);
            $table->unsignedSmallInteger('skipped_steps')->default(0);
            $table->timestamp('triggered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('root_event', 'chains_root_event_idx');
            $table->index(['aggregate_type', 'aggregate_id'], 'chains_aggregate_idx');
            $table->index(['status', 'updated_at'], 'chains_status_updated_idx');
            $table->index(['school_id', 'status'], 'chains_school_status_idx');
            $table->index(['academic_year_id', 'status'], 'chains_year_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chains');
    }
};
