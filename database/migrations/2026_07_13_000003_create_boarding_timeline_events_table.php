<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boarding_timeline_events', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('event_type', 32)->index()
                ->comment('check_in, room_transfer, leave_approved, returned, hospitalized, '
                    .'recovered, visit_approved, violation, reward, expelled, transfer, holiday, '
                    .'permit_rejected, leave_overdue, special_permission, leave_started');

            $table->uuid('student_id')->index();
            $table->uuid('dormitory_id')->index()->nullable();
            $table->uuid('room_id')->nullable();
            $table->uuid('boarding_policy_id')->nullable();

            $table->dateTime('event_at')->index();

            $table->json('subject_refs')->nullable()
                ->comment('Source record references (permit_id, visit_id, violation_id, etc.)');

            $table->json('payload')->nullable()
                ->comment('Domain-specific data: destination, companion, reason, status, etc.');

            $table->boolean('is_special_permission')->default(false)
                ->comment('True if this event is a special permission (quota bypass).');

            $table->uuid('recorded_by')->nullable();
            $table->uuid('source_actor_id')->nullable()
                ->comment('User/system that triggered the event.');
            $table->string('source_system', 32)->default('dormitory')
                ->comment('dormitory | academic | system | rules-engine.');

            $table->timestamps();

            $table->index(['student_id', 'event_at']);
            $table->index(['dormitory_id', 'event_type', 'event_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boarding_timeline_events');
    }
};
