<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_mutations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('supply_stock_id');
            $table->uuid('work_unit_id');
            $table->uuid('school_id')->nullable();
            $table->enum('mutation_type', ['masuk', 'keluar', 'penyesuaian', 'retur'])
                ->comment('masuk=restock, keluar=dipakai, penyesuaian=audit, retur=dikembalikan');
            $table->date('mutation_date');
            $table->integer('quantity');
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->string('reference_type', 50)->nullable()
                ->comment('procurement_request | manual | audit_tahunan');
            $table->char('reference_id', 36)->nullable();
            $table->uuid('recipient_user_id')->nullable()
                ->comment('GTK yang mengambil ATK (untuk mutasi keluar)');
            $table->string('recipient_unit', 100)->nullable()
                ->comment('Unit kerja yang mengambil ATK');
            $table->text('purpose')->nullable();
            $table->uuid('recorded_by');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('supply_stock_id')->references('id')->on('supply_stocks')->cascadeOnDelete();
            $table->foreign('work_unit_id')->references('id')->on('work_units')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('recipient_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users');

            $table->index(['supply_stock_id', 'mutation_date']);
            $table->index(['work_unit_id', 'mutation_type', 'mutation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_mutations');
    }
};
