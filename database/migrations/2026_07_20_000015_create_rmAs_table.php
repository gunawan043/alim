<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rmas', function (Blueprint $table) {
            $table->id();
            $table->string('rma_number', 50)->unique();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('quality_check_id')->nullable();
            $table->unsignedBigInteger('goods_receipt_id')->nullable();
            $table->unsignedBigInteger('vendor_id');
            $table->string('vendor_reference')->nullable();
            $table->enum('status', [
                'open',
                'approved',
                'in_return',
                'received_by_vendor',
                'replacement_received',
                'refunded',
                'credited',
                'closed',
                'cancelled',
            ])->default('open');
            $table->enum('type', ['defective', 'wrong_item', 'missing', 'damaged', 'non_conforming']);
            $table->integer('quantity');
            $table->date('request_date');
            $table->date('estimated_return_date')->nullable();
            $table->date('actual_return_date')->nullable();
            $table->text('description');
            $table->text('resolution')->nullable();
            $table->decimal('refund_amount', 18, 2)->default(0);
            $table->decimal('cost_deduction', 18, 2)->default(0);
            $table->json('evidence')->nullable();
            $table->text('vendor_response')->nullable();
            $table->timestamp('vendor_responded_at')->nullable();
            $table->char('created_by', 36);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders')
                ->cascadeOnDelete();
            $table->foreign('quality_check_id')
                ->references('id')
                ->on('quality_checks')
                ->nullOnDelete();
            $table->foreign('goods_receipt_id')
                ->references('id')
                ->on('goods_receipts')
                ->nullOnDelete();
            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
                ->cascadeOnDelete();
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->index('status');
            $table->index('request_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rmas');
    }
};
