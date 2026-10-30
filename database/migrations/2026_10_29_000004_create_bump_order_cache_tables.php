<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bump_order_cache', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('work_unit_id')->nullable()->constrained('work_units')->nullOnDelete();
            $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->string('external_reference', 120)->unique();
            $table->string('pos_order_number', 80)->unique();
            $table->string('payment_method', 30)->nullable();
            $table->string('status', 30)->default('pending');
            $table->string('payment_status', 30)->default('unpaid');
            $table->decimal('total', 16, 2)->default(0);
            $table->json('payload')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->index(['work_unit_id', 'status']);
        });

        Schema::create('bump_order_cache_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bump_order_cache_id')->constrained('bump_order_cache')->cascadeOnDelete();
            $table->string('status', 40);
            $table->string('payment_status', 40)->nullable();
            $table->string('source', 20)->default('bump');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bump_order_cache_logs');
        Schema::dropIfExists('bump_order_cache');
    }
};
