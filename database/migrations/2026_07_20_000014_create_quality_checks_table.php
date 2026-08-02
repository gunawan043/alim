<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_checks', function (Blueprint $table) {
            $table->id();
            $table->string('qc_number', 50)->unique();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('goods_receipt_id')->nullable();
            $table->enum('status', [
                'pending',
                'in_progress',
                'passed',
                'failed',
                'partially_passed',
                'cancelled',
            ])->default('pending');
            $table->date('inspection_date');
            $table->char('inspector_id', 36)->nullable();
            $table->string('inspector_name', 200)->nullable();
            $table->json('inspection_criteria')->nullable();
            $table->json('inspection_results')->nullable();
            $table->integer('sample_size')->default(0);
            $table->integer('passed_quantity')->default(0);
            $table->integer('failed_quantity')->default(0);
            $table->decimal('pass_rate', 5, 2)->default(0);
            $table->text('failure_reasons')->nullable();
            $table->text('recommendations')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders')
                ->cascadeOnDelete();
            $table->foreign('goods_receipt_id')
                ->references('id')
                ->on('goods_receipts')
                ->nullOnDelete();
            $table->foreign('inspector_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index('status');
            $table->index('inspection_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_checks');
    }
};
