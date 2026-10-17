<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 50)->unique();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('rfq_id')->nullable();
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->enum('status', [
                'draft',
                'sent',
                'accepted',
                'rejected',
                'in_production',
                'ready_to_ship',
                'shipped',
                'in_transit',
                'delivered',
                'qc_in_progress',
                'qc_passed',
                'qc_failed',
                'invoiced',
                'paid',
                'closed',
                'cancelled',
            ])->default('draft');
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->string('delivery_address', 500)->nullable();
            $table->text('shipping_notes')->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->text('special_instructions')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount', 18, 2)->default(0);
            $table->decimal('tax', 18, 2)->default(0);
            $table->decimal('shipping_cost', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->string('currency', 10)->default('IDR');
            $table->char('created_by', 36);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->unsignedBigInteger('accepted_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
                ->cascadeOnDelete();
            $table->foreign('rfq_id')
                ->references('id')
                ->on('rfq_requests')
                ->nullOnDelete();
            $table->foreign('quotation_id')
                ->references('id')
                ->on('quotations')
                ->nullOnDelete();
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('accepted_by')
                ->references('id')
                ->on('vendors')
                ->nullOnDelete();

            $table->index('status');
            $table->index('order_date');
            $table->index('expected_delivery_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
