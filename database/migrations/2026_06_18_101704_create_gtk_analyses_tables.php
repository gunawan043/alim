<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GTK Analytical Intelligence tables.
 *
 * Stores the result of workload/gap analysis runs triggered by events
 * from GtkProfile, TeachingAssignment, and StudyGroupSubject changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtk_analysis_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('school_id')
                ->nullable()
                ->constrained('schools')
                ->nullOnDelete();

            $table->foreignUuid('academic_year_id')
                ->nullable()
                ->constrained('academic_years')
                ->nullOnDelete();

            $table->string('scope', 32)->default('school')
                ->comment('school | global | teacher | subject');

            $table->string('trigger_source', 64)->nullable()
                ->comment('GtkProfileUpdated | TeachingAssignmentChanged | StudyGroupSubjectChanged | manual | command');

            $table->string('trigger_ref_id', 64)->nullable();

            $table->unsignedTinyInteger('status')->default(0)
                ->comment('0=pending, 1=processing, 2=completed, 3=failed');

            $table->json('summary')->nullable()
                ->comment('Headline numbers: total_guru, total_jam_kebutuhan, total_jam_tersedia, gap_total');

            $table->json('context')->nullable()
                ->comment('Snapshot of parameters used: filters, thresholds, settings');

            $table->text('error_message')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();

            $table->index(['school_id', 'academic_year_id', 'status'], 'idx_gar_scope_status');
            $table->index('created_at');
        });

        Schema::create('gtk_gap_summaries', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('analysis_run_id')
                ->constrained('gtk_analysis_runs')
                ->cascadeOnDelete();

            $table->foreignUuid('school_id')
                ->nullable()
                ->constrained('schools')
                ->nullOnDelete();

            $table->foreignUuid('academic_year_id')
                ->nullable()
                ->constrained('academic_years')
                ->nullOnDelete();

            $table->foreignUuid('subject_id')
                ->nullable()
                ->constrained('subjects')
                ->nullOnDelete();

            $table->foreignUuid('study_group_id')
                ->nullable()
                ->constrained('study_groups')
                ->nullOnDelete();

            $table->foreignUuid('teacher_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('dimension', 32)
                ->comment('subject | teacher | study_group | grade_level');

            $table->string('dimension_label')->nullable();

            $table->decimal('hours_needed', 8, 2)->default(0)
                ->comment('Total jam mengajar yang dibutuhkan pada dimensi ini');

            $table->decimal('hours_available', 8, 2)->default(0)
                ->comment('Total jam tersedia / ter-assign');

            $table->decimal('hours_gap', 8, 2)->default(0)
                ->comment('Selisih (available - needed); negatif = defisit');

            $table->unsignedInteger('teacher_count')->default(0);
            $table->unsignedInteger('assignment_count')->default(0);
            $table->unsignedInteger('group_count')->default(0);

            $table->string('status', 24)->default('balanced')
                ->comment('deficit | surplus | balanced | uncovered');

            $table->decimal('ideal_min_hours', 8, 2)->nullable();
            $table->decimal('ideal_max_hours', 8, 2)->nullable();

            $table->json('details')->nullable()
                ->comment('Per-teacher breakdown, per-class hours, etc.');

            $table->timestamps();

            $table->index(['analysis_run_id', 'dimension'], 'idx_ggs_run_dim');
            $table->index(['school_id', 'academic_year_id', 'dimension'], 'idx_ggs_scope');
            $table->index(['subject_id', 'academic_year_id'], 'idx_ggs_subject_year');
            $table->index(['teacher_id', 'academic_year_id'], 'idx_ggs_teacher_year');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtk_gap_summaries');
        Schema::dropIfExists('gtk_analysis_runs');
    }
};
