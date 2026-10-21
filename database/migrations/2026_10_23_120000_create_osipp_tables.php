<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('osipp_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->string('name', 120);
            $table->string('decree_number', 100)->nullable();
            $table->date('decree_date')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->foreignUuid('supervisor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('draft'); // draft | active | ended
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'academic_year_id', 'name'], 'osipp_period_unique');
            $table->index(['school_id', 'status'], 'osipp_period_school_status');
            $table->index(['school_id', 'start_date'], 'osipp_period_school_date');
        });

        Schema::create('osipp_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('period_id')->constrained('osipp_periods')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('position', 100);
            $table->string('position_code', 50)->nullable();
            $table->string('division', 100)->nullable();
            $table->string('member_role', 30)->default('pengurus'); // pengurus | anggota
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('active'); // active | inactive
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['period_id', 'student_id'], 'osipp_member_unique');
            $table->index(['period_id', 'status'], 'osipp_member_period_status');
            $table->index(['period_id', 'position_code'], 'osipp_member_period_position');
        });

        Schema::create('osipp_programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('period_id')->constrained('osipp_periods')->cascadeOnDelete();
            $table->string('name', 180);
            $table->text('description')->nullable();
            $table->string('category', 80)->nullable();
            $table->foreignUuid('responsible_member_id')->nullable()->constrained('osipp_members')->nullOnDelete();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('target_output')->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('status', 20)->default('planned'); // planned | running | completed | cancelled
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['period_id', 'status'], 'osipp_program_period_status');
            $table->index(['period_id', 'start_date'], 'osipp_program_period_date');
        });

        Schema::create('osipp_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('period_id')->constrained('osipp_periods')->cascadeOnDelete();
            $table->foreignUuid('program_id')->nullable()->constrained('osipp_programs')->nullOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('category', 80)->nullable();
            $table->date('activity_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location', 180)->nullable();
            $table->unsignedInteger('participant_count')->default(0);
            $table->foreignUuid('responsible_member_id')->nullable()->constrained('osipp_members')->nullOnDelete();
            $table->string('status', 20)->default('draft'); // draft | submitted | verified | revision | rejected | completed
            $table->timestamp('submitted_at')->nullable();
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->string('documentation_url', 500)->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['period_id', 'status'], 'osipp_activity_period_status');
            $table->index(['period_id', 'activity_date'], 'osipp_activity_period_date');
            $table->index(['program_id', 'activity_date'], 'osipp_activity_program_date');
        });

        Schema::create('osipp_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('period_id')->constrained('osipp_periods')->cascadeOnDelete();
            $table->foreignUuid('program_id')->nullable()->constrained('osipp_programs')->nullOnDelete();
            $table->foreignUuid('activity_id')->nullable()->constrained('osipp_activities')->nullOnDelete();
            $table->string('document_type', 40)->default('other'); // proposal | lpj | surat | notulen | daftar_hadir | other
            $table->string('title', 180);
            $table->string('file_path', 500)->nullable();
            $table->string('external_url', 500)->nullable();
            $table->string('status', 20)->default('draft'); // draft | submitted | verified | revision | rejected
            $table->timestamp('submitted_at')->nullable();
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['period_id', 'status'], 'osipp_document_period_status');
            $table->index(['period_id', 'document_type'], 'osipp_document_period_type');
            $table->index(['activity_id', 'program_id'], 'osipp_document_reference');
        });

        Schema::create('osipp_coachings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('period_id')->constrained('osipp_periods')->cascadeOnDelete();
            $table->foreignUuid('member_id')->nullable()->constrained('osipp_members')->nullOnDelete();
            $table->string('coaching_type', 20)->default('kelompok'); // individu | kelompok
            $table->date('coaching_date');
            $table->string('topic', 180);
            $table->foreignUuid('mentor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('location', 180)->nullable();
            $table->text('summary')->nullable();
            $table->text('follow_up')->nullable();
            $table->string('status', 20)->default('completed'); // planned | completed | cancelled
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['period_id', 'coaching_date'], 'osipp_coaching_period_date');
            $table->index(['period_id', 'status'], 'osipp_coaching_period_status');
        });

        Schema::create('osipp_coaching_attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('coaching_id')->constrained('osipp_coachings')->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained('osipp_members')->cascadeOnDelete();
            $table->string('status', 20)->default('present'); // present | absent | excused
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['coaching_id', 'member_id'], 'osipp_coaching_attendance_unique');
        });

        Schema::create('osipp_evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('period_id')->constrained('osipp_periods')->cascadeOnDelete();
            $table->string('evaluation_type', 30)->default('akhir'); // tengah | akhir
            $table->date('evaluation_date');
            $table->foreignUuid('evaluator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('scores')->nullable();
            $table->decimal('total_score', 5, 2)->default(0);
            $table->string('grade', 2)->nullable();
            $table->text('strengths')->nullable();
            $table->text('recommendations')->nullable();
            $table->string('status', 20)->default('draft'); // draft | final
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['period_id', 'evaluation_date'], 'osipp_evaluation_period_date');
            $table->index(['period_id', 'status'], 'osipp_evaluation_period_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('osipp_evaluations');
        Schema::dropIfExists('osipp_coaching_attendances');
        Schema::dropIfExists('osipp_coachings');
        Schema::dropIfExists('osipp_documents');
        Schema::dropIfExists('osipp_activities');
        Schema::dropIfExists('osipp_programs');
        Schema::dropIfExists('osipp_members');
        Schema::dropIfExists('osipp_periods');
    }
};
