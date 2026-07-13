<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_event_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('asset_id');
            $table->string('event_type');       // asset_created, qr_generated, asset_moved, asset_audit,
            // maintenance_completed, repair_completed,
            // warranty_expired, procurement_completed,
            // condition_changed, damage_reported,
            // loan_created, loan_returned,
            // stock_opname_verified
            $table->text('event_detail')->nullable(); // JSON payload of event data
            $table->unsignedBigInteger('actor_id')->nullable(); // User who performed the action
            $table->timestamps();

            $table->index('asset_id');
            $table->index('event_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_event_logs');
    }
};
