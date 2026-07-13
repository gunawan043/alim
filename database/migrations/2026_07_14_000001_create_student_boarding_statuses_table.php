<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Single source of truth for "where is this student RIGHT NOW".
 *
 * Hard invariant: at most one row per student_id (UNIQUE).
 * If a row is absent, the student is implicitly CHECKED_OUT.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_boarding_statuses', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignUuid('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            // 5 mutually exclusive statuses (see App\Models\StudentBoardingStatus)
            $table->string('status', 32)->index();

            $table->foreignUuid('dormitory_id')
                ->nullable()
                ->constrained('dormitories')
                ->nullOnDelete();

            $table->foreignUuid('room_id')
                ->nullable()
                ->constrained('dormitory_rooms')
                ->nullOnDelete();

            // When the current status became active
            $table->timestampTz('effective_from')->useCurrent();

            // For ON_LEAVE / AT_HOSPITAL: when they're expected back
            $table->timestampTz('expected_return_at')->nullable();

            // Polymorphic reference to the source record that drove this status
            // (DormitoryPermit, DormitoryVisitLog, etc.)
            $table->string('context_subject_type', 64)->nullable();
            $table->string('context_subject_id', 64)->nullable();

            $table->text('note')->nullable();

            $table->foreignUuid('changed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Last timeline event that produced this status
            $table->timestampTz('last_event_at')->nullable();

            $table->timestampsTz();

            // HARD invariant: one status row per student.
            $table->unique('student_id', 'uniq_sbs_student');

            // Common read patterns for the dashboard
            $table->index(['status', 'dormitory_id'], 'idx_sbs_status_dorm');
            $table->index('expected_return_at', 'idx_sbs_expected_return');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_boarding_statuses');
    }
};