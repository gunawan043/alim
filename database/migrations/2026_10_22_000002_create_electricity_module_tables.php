<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('electricity_meters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('meter_type', 30)->default('prabayar');
            $table->string('meter_number', 80)->unique();
            $table->string('location', 191)->nullable();
            $table->decimal('default_amount', 15, 2)->nullable();
            $table->unsignedTinyInteger('payment_due_day')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['meter_type', 'is_active']);
        });

        Schema::create('electricity_token_purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('meter_id');
            $table->date('period');
            $table->decimal('amount', 15, 2);
            $table->date('purchased_at')->nullable();
            $table->string('receipt_path')->nullable();
            $table->text('receipt_url')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('recorded_by')->nullable();
            $table->timestamps();

            $table->foreign('meter_id')->references('id')->on('electricity_meters')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['meter_id', 'period']);
            $table->index('period');
        });

        Schema::create('electricity_outages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->dateTime('occurred_at');
            $table->string('location', 191);
            $table->uuid('work_unit_id')->nullable();
            $table->uuid('reporter_id')->nullable();
            $table->string('reporter_name', 191)->nullable();
            $table->string('outage_type', 30)->default('tidak_terencana');
            $table->text('cause')->nullable();
            $table->dateTime('restored_at')->nullable();
            $table->string('status', 30)->default('open');
            $table->text('notes')->nullable();
            $table->string('before_photo_path')->nullable();
            $table->text('before_photo_url')->nullable();
            $table->string('after_photo_path')->nullable();
            $table->text('after_photo_url')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('work_unit_id')->references('id')->on('work_units')->nullOnDelete();
            $table->foreign('reporter_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['status', 'occurred_at']);
        });

        Schema::create('electricity_outage_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('outage_id');
            $table->text('action');
            $table->uuid('officer_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('outage_id')->references('id')->on('electricity_outages')->cascadeOnDelete();
            $table->foreign('officer_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electricity_outage_actions');
        Schema::dropIfExists('electricity_outages');
        Schema::dropIfExists('electricity_token_purchases');
        Schema::dropIfExists('electricity_meters');
    }
};
