<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quotation_id');
            $table->unsignedBigInteger('rfq_item_id')->nullable();
            $table->string('item_name');
            $table->text('specification')->nullable();
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('origin', 100)->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 18, 2);
            $table->decimal('line_total', 18, 2);
            $table->integer('warranty_months')->nullable();
            $table->string('warranty_type', 50)->nullable();
            $table->text('notes')->nullable();

            $table->foreign('quotation_id')
                ->references('id')
                ->on('quotations')
                ->cascadeOnDelete();
            $table->foreign('rfq_item_id')
                ->references('id')
                ->on('rfq_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
