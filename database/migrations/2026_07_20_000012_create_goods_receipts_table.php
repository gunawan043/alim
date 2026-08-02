<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('gr_number', 50)->unique();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('delivery_id')->nullable();
            $table->date('receipt_date');
            $table->enum('status', [
                'received',
                'under_inspection',
                'accepted',
                'rejected',
                'partial',
                'closed',
            ])->default('received');
            $table->string('warehouse_location', 200)->nullable();
            $table->char('received_by', 36);
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->string('supplier_delivery_note', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders')
                ->cascadeOnDelete();
            $table->foreign('delivery_id')
                ->references('id')
                ->on('delivery_tracking')
                ->nullOnDelete();
            $table->foreign('received_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->nullOnDelete();

            $table->index('status');
            $table->index('receipt_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};
