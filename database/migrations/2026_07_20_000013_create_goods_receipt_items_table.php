<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('goods_receipt_id');
            $table->unsignedBigInteger('purchase_order_item_id');
            $table->string('item_name');
            $table->integer('expected_quantity');
            $table->integer('received_quantity');
            $table->integer('accepted_quantity')->default(0);
            $table->integer('rejected_quantity')->default(0);
            $table->text('discrepancy_notes')->nullable();
            $table->foreign('goods_receipt_id')
                ->references('id')
                ->on('goods_receipts')
                ->cascadeOnDelete();
            $table->foreign('purchase_order_item_id')
                ->references('id')
                ->on('purchase_order_items')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
    }
};
