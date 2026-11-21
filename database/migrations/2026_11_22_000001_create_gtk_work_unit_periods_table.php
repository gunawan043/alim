<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive lifecycle history for GTK work-unit placements.
     *
     * The existing gtk_work_unit table remains the current projection and keeps
     * its unique (user_id, work_unit_id) constraint. This table records periods
     * without altering existing readers.
     */
    public function up(): void
    {
        Schema::create('gtk_work_unit_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('placement_id')
                ->nullable()
                ->constrained('gtk_work_unit')
                ->nullOnDelete();

            // History must survive deletion of the parent; RESTRICT by design.
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignUuid('work_unit_id')
                ->constrained('work_units')
                ->restrictOnDelete();

            $table->foreignUuid('position_id')
                ->nullable()
                ->constrained('structural_positions')
                ->nullOnDelete();

            $table->foreignUuid('decree_id')
                ->nullable()
                ->constrained('institution_decrees')
                ->nullOnDelete();

            $table->string('status', 16)->default('active');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('ended_reason', 255)->nullable();

            $table->foreignUuid('ended_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['user_id', 'work_unit_id', 'status'], 'gtk_wup_user_unit_status_idx');
            $table->index(['start_date', 'end_date'], 'gtk_wup_dates_idx');
            $table->index('placement_id', 'gtk_wup_placement_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtk_work_unit_periods');
    }
};
