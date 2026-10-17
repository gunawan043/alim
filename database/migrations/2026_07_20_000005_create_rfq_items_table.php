<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfq_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rfq_id');
            $table->string('item_name');
            $table->text('specification')->nullable();
            $table->integer('quantity');
            $table->string('unit');
            $table->text('notes')->nullable();
            $table->foreign('rfq_id')
                ->references('id')
                ->on('rfq_requests')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_items');
    }
};
