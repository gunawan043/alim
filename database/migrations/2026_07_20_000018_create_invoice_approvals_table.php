<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('approval_number', 50)->unique();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('purchase_order_id');
            $table->string('invoice_number', 100);
            $table->string('supplier_invoice_number')->nullable();
            $table->string('attachment_path', 500)->nullable();
            $table->decimal('total_amount', 18, 2);
            $table->string('currency', 10)->default('IDR');
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->enum('status', [
                'draft',
                'submitted',
                'in_review',
                'approved',
                'rejected',
                'partially_approved',
                'paid',
                'cancelled',
            ])->default('draft');
            $table->text('notes')->nullable();
            $table->text('comments')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
                ->cascadeOnDelete();
            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders')
                ->cascadeOnDelete();

            $table->index('status');
            $table->index('invoice_date');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_approvals');
    }
};
