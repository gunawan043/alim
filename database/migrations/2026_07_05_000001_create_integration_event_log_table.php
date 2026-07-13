<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_event_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('event_name', 96)->index();
            $table->uuid('aggregate_id')->nullable()->index();
            $table->string('aggregate_type', 64)->nullable()->index();
            $table->string('source_module', 32)->index();
            $table->string('target_module', 32)->nullable()->index();
            $table->string('status', 16)->default('processed')->index();
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->json('payload')->nullable();
            $table->text('error')->nullable();
            $table->uuid('dispatched_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['event_name', 'created_at']);
            $table->index(['source_module', 'target_module']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_event_log');
    }
};