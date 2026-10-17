<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_movements', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->uuid('movement_number')->unique();
            $table->foreignUuid('asset_id')->constrained('assets')->onDelete('cascade');
            $table->string('work_unit_id', 36)->nullable();
            $table->foreignUuid('from_room_id')->nullable()->constrained('asset_rooms')->nullOnDelete();
            $table->foreignUuid('to_room_id')->nullable()->constrained('asset_rooms')->nullOnDelete();
            $table->foreignUuid('from_holder_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('to_holder_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 100)->nullable();
            $table->text('justification')->nullable();
            $table->enum('status', [
                'requested', 'approved', 'rejected', 'in_transit',
                'received', 'verified', 'completed', 'cancelled',
            ])->default('requested')->index();
            $table->foreignUuid('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('carrier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('receiver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('verifier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('in_transit_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('condition_snapshot')->nullable();
            $table->json('condition_after')->nullable();
            $table->text('verification_notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['asset_id', 'status']);
        });

        Schema::create('work_order_progress_notes', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('note');
            $table->enum('note_type', ['observation', 'issue', 'resolution', 'comment', 'pause_reason'])->default('comment');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['work_order_id', 'created_at']);
        });

        Schema::create('work_order_pause_events', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason_code', 50)->nullable();
            $table->text('reason_text')->nullable();
            $table->timestamp('paused_at');
            $table->timestamp('resumed_at')->nullable();
            $table->unsignedInteger('pause_duration_seconds')->nullable();
            $table->timestamps();
            $table->index(['work_order_id', 'paused_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_pause_events');
        Schema::dropIfExists('work_order_progress_notes');
        Schema::dropIfExists('asset_movements');
    }
};
