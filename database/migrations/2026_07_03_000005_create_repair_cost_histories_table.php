<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_cost_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('asset_id');
            $table->foreignId('work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $table->foreignId('repair_request_id')->nullable()->constrained('repair_requests')->nullOnDelete();
            $table->string('cost_category'); // labor, sparepart, external_service, transport, other
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->date('incurred_date');
            $table->string('document_number')->nullable(); // invoice or receipt
            $table->string('vendor_name')->nullable();
            $table->uuid('recorded_by')->nullable();
            $table->timestamps();

            $table->index('asset_id');
            $table->index('cost_category');
            $table->index('incurred_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_cost_histories');
    }
};
