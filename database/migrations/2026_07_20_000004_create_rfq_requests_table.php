<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfq_requests', function (Blueprint $table) {
            $table->id();
            $table->string('rfq_number', 50)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', [
                'draft',
                'published',
                'awaiting_quotations',
                'under_evaluation',
                'awarded',
                'closed',
                'cancelled',
            ])->default('draft');
            $table->date('quotation_deadline');
            $table->date('expected_delivery_date')->nullable();
            $table->string('delivery_location', 255)->nullable();
            $table->text('terms_conditions')->nullable();
            $table->char('created_by', 36);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('awarded_quotation_id')->nullable();
            $table->timestamps();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->index('status');
            $table->index('quotation_deadline');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_requests');
    }
};
