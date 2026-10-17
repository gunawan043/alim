<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot table connecting study_group ↔ subject ↔ teacher.
     *
     * This is the FOUNDATION of the academic cascade:
     * when a row is created (Subject assigned to StudyGroup),
     * StudyGroupSubjectObserver dispatches SubjectAssignedToStudyGroup event,
     * which triggers provisioning of:
     *   - teacher_admin_books (for grades + attendance)
     *   - kktp context lookup
     *   - per-student placeholders in admin_nilai_sumatif
     *   - raport registration updates
     */
    public function up(): void
    {
        Schema::create('study_group_subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('school_id');
            $table->uuid('academic_year_id');

            $table->foreignUuid('study_group_id')
                ->constrained('study_groups')
                ->cascadeOnDelete();

            $table->foreignUuid('subject_id')
                ->constrained('subjects')
                ->cascadeOnDelete();

            $table->foreignUuid('teacher_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->decimal('weekly_hours', 4, 1)->default(2.0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();

            $table->unique(
                ['study_group_id', 'subject_id', 'academic_year_id'],
                'sgs_unique_assignment'
            );

            $table->index(['school_id', 'academic_year_id', 'is_active'], 'sgs_school_year_active');
            $table->index(['study_group_id', 'is_active'], 'sgs_rombel_active');
            $table->index(['subject_id', 'is_active'], 'sgs_subject_active');
            $table->index(['teacher_id', 'academic_year_id'], 'sgs_teacher_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_group_subjects');
    }
};
