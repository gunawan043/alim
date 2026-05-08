<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_booking_conflicts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('room_id');
            $table->uuid('booking_id_a');
            $table->uuid('booking_id_b');
            $table->date('conflict_date');
            $table->timestamp('detected_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('room_id')->references('id')->on('asset_rooms')->cascadeOnDelete();
            $table->foreign('booking_id_a')->references('id')->on('room_bookings')->cascadeOnDelete();
            $table->foreign('booking_id_b')->references('id')->on('room_bookings')->cascadeOnDelete();

            $table->index(['room_id', 'conflict_date']);
        });
    }

    public function down(): void { Schema::dropIfExists('room_booking_conflicts'); }
};
