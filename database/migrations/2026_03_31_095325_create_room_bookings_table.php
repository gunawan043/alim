<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('room_id');
            $table->uuid('work_unit_id');
            $table->uuid('school_id')->nullable();
            $table->uuid('booked_by');
            $table->text('purpose');
            $table->string('event_name', 191)->nullable();
            $table->integer('participants_count')->nullable();
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->time('setup_time')->nullable()
                  ->comment('Jam mulai persiapan sebelum acara');
            $table->enum('status', [
                'pending', 'approved', 'rejected', 'cancelled', 'completed',
            ])->default('pending');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->time('actual_start_time')->nullable();
            $table->time('actual_end_time')->nullable();
            $table->text('condition_before')->nullable();
            $table->text('condition_after')->nullable();
            $table->uuid('related_agenda_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('room_id')->references('id')->on('asset_rooms')->cascadeOnDelete();
            $table->foreign('work_unit_id')->references('id')->on('work_units')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('booked_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('related_agenda_id')->references('id')->on('agendas')->nullOnDelete();

            $table->index(['room_id', 'booking_date', 'status']);
            $table->index(['booked_by', 'booking_date']);
            $table->index(['school_id', 'booking_date']);
        });
    }

    public function down(): void { Schema::dropIfExists('room_bookings'); }
};
