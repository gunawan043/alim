<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('supply_stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_unit_id');
            $table->uuid('school_id')->nullable();
            $table->uuid('supply_category_id')->nullable();
            $table->string('item_name', 191);
            $table->string('item_code', 30)->nullable();
            $table->string('brand', 100)->nullable();
            $table->enum('unit', [
                'pcs', 'rim', 'box', 'roll', 'liter', 'kg', 'set', 'pak', 'lusin', 'lainnya',
            ])->default('pcs');
            $table->integer('current_stock')->default(0);
            $table->integer('minimum_stock')->default(0)
                  ->comment('Trigger notifikasi jika current_stock <= minimum_stock');
            $table->integer('maximum_stock')->nullable();
            $table->decimal('last_price', 15, 2)->nullable()
                  ->comment('Harga terakhir per unit saat restock');
            $table->string('storage_location', 100)->nullable()
                  ->comment('Lokasi penyimpanan, misal: Lemari A-2, Rak 3');
            $table->date('last_restocked_at')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('work_unit_id')->references('id')->on('work_units')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('supply_category_id')
                  ->references('id')->on('supply_categories')->nullOnDelete();

            $table->index(['work_unit_id', 'is_active']);
            $table->index(['current_stock', 'minimum_stock']);
        });
    }

    public function down(): void { Schema::dropIfExists('supply_stocks'); }
};
