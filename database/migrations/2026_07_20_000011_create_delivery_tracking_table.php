<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_tracking', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');
            $table->string('tracking_number', 100)->nullable();
            $table->string('courier', 100)->nullable();
            $table->string('service_type', 50)->nullable();
            $table->date('dispatched_date')->nullable();
            $table->date('estimated_arrival')->nullable();
            $table->timestamp('actual_arrival')->nullable();
            $table->enum('status', [
                'pending',
                'picked_up',
                'in_transit',
                'out_for_delivery',
                'delivered',
                'failed',
                'returned',
            ])->default('pending');
            $table->text('current_location')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->json('tracking_events')->nullable();
            $table->char('recipient_user_id', 36)->nullable();
            $table->string('recipient_name', 200)->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders')
                ->cascadeOnDelete();
            $table->foreign('recipient_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index('tracking_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_tracking');
    }
};
