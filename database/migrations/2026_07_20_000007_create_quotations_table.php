<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number', 50)->unique();
            $table->unsignedBigInteger('rfq_id');
            $table->unsignedBigInteger('vendor_id');
            $table->enum('status', [
                'draft',
                'submitted',
                'under_review',
                'accepted',
                'rejected',
                'withdrawn',
                'expired',
            ])->default('draft');
            $table->date('quotation_date');
            $table->date('valid_until');
            $table->integer('lead_time_days')->nullable();
            $table->text('terms')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount', 18, 2)->default(0);
            $table->decimal('tax', 18, 2)->default(0);
            $table->decimal('shipping_cost', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->string('currency', 10)->default('IDR');
            $table->char('submitted_by', 36)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->char('reviewed_by', 36)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->foreign('rfq_id')
                ->references('id')
                ->on('rfq_requests')
                ->cascadeOnDelete();
            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
                ->cascadeOnDelete();
            $table->foreign('submitted_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('reviewed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index('status');
            $table->index(['rfq_id', 'vendor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
