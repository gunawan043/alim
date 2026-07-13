<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('order_number')->unique(); // WO-2026-0001
            $table->foreignId('repair_request_id')->constrained('repair_requests')->cascadeOnDelete();
            $table->uuid('asset_id');
            $table->foreignUuid('assignee_id')->nullable()->constrained('users')->nullOnDelete(); // teknisi
            $table->string('type')->default('internal')->comment('internal, external/vendor');
            $table->text('scope_of_work');
            $table->date('scheduled_date')->nullable();
            $table->timestamp('actual_start')->nullable();
            $table->timestamp('actual_end')->nullable();
            $table->string('status')->default('draft')->comment('draft, dispatched, in_progress, waiting_parts, completed, closed, cancelled');
            $table->text('completion_notes')->nullable();
            $table->decimal('total_cost', 12, 2)->default(0); // labor + spare parts
            $table->timestamps();

            $table->index('status');
            $table->index('asset_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
