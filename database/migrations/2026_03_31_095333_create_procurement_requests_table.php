<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_request_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('procurement_request_id');
            $table->uuid('asset_category_id')->nullable();
            $table->string('item_name', 191);
            $table->text('specification')->nullable();
            $table->integer('quantity');
            $table->string('unit', 30)->default('pcs')
                ->comment('Pcs, Unit, Rim, Set, Box, dll');
            $table->decimal('estimated_price_per_unit', 15, 2)->nullable();
            $table->decimal('total_estimated_price', 15, 2)->nullable();
            $table->text('purpose')->nullable();
            $table->uuid('room_id')->nullable()
                ->comment('Rencana penempatan aset setelah diterima');
            $table->integer('actual_quantity_received')->nullable();
            $table->decimal('actual_price_per_unit', 15, 2)->nullable();
            $table->date('received_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('procurement_request_id')
                ->references('id')->on('procurement_requests')->cascadeOnDelete();
            $table->foreign('asset_category_id')
                ->references('id')->on('asset_categories')->nullOnDelete();
            $table->foreign('room_id')
                ->references('id')->on('asset_rooms')->nullOnDelete();

            $table->index('procurement_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_request_items');
    }
};
