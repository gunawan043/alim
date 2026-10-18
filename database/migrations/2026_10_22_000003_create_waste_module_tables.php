<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('source_type', 50)->default('lainnya');
            $table->uuid('work_unit_id')->nullable();
            $table->uuid('building_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->foreign('work_unit_id')->references('id')->on('work_units')->nullOnDelete();
            $table->foreign('building_id')->references('id')->on('asset_buildings')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('waste_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('source_id');
            $table->date('log_date');
            $table->string('waste_type', 30);
            $table->decimal('weight_kg', 10, 2)->default(0);
            $table->unsignedInteger('sacks_count')->default(0);
            $table->decimal('volume_m3', 10, 2)->nullable();
            $table->string('sorting_status', 30)->default('terpilah');
            $table->string('destination', 50)->default('tps');
            $table->string('photo_path')->nullable();
            $table->text('photo_url')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('recorded_by')->nullable();
            $table->string('status', 30)->default('submitted');
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('coordinator_note')->nullable();
            $table->timestamps();
            $table->foreign('source_id')->references('id')->on('waste_sources')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['log_date', 'waste_type']);
            $table->index(['source_id', 'log_date']);
        });

        Schema::create('waste_handovers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('handover_date');
            $table->string('destination', 191);
            $table->string('waste_type', 30)->nullable();
            $table->decimal('total_weight_kg', 10, 2)->default(0);
            $table->unsignedInteger('total_sacks')->default(0);
            $table->string('receipt_number', 100)->nullable();
            $table->string('receipt_path')->nullable();
            $table->text('receipt_url')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('photo_url')->nullable();
            $table->uuid('officer_id')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->foreign('officer_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index('handover_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_handovers');
        Schema::dropIfExists('waste_logs');
        Schema::dropIfExists('waste_sources');
    }
};
