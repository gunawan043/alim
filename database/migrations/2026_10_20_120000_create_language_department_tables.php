<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('language_vocabulary_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignUuid('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->string('title', 180);
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('location_school', 180)->nullable();
            $table->string('location_dorm', 180)->nullable();
            $table->unsignedSmallInteger('min_words')->default(10);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['academic_year_id', 'period_start']);
        });

        Schema::create('language_vocabularies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('period_id')->constrained('language_vocabulary_periods')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(1);
            $table->string('arabic', 180);
            $table->string('meaning', 180);
            $table->text('example_sentence')->nullable();
            $table->timestamps();

            $table->index(['period_id', 'position']);
        });

        Schema::create('language_mustami_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('period_id')->constrained('language_vocabulary_periods')->cascadeOnDelete();
            $table->foreignUuid('mustami_id')->constrained('users')->cascadeOnDelete();
            $table->string('scope', 150)->nullable();
            $table->foreignUuid('dormitory_id')->nullable()->constrained('dormitories')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['period_id', 'mustami_id', 'scope'], 'lang_mustami_period_user_scope_unique');
        });

        Schema::create('language_mufradat_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('period_id')->constrained('language_vocabulary_periods')->cascadeOnDelete();
            $table->foreignUuid('mustami_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('time_start')->nullable();
            $table->time('time_end')->nullable();
            $table->string('place', 150)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['period_id', 'day_of_week']);
        });

        Schema::create('language_mufradat_setorans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('period_id')->constrained('language_vocabulary_periods')->cascadeOnDelete();
            $table->foreignUuid('schedule_id')->nullable()->constrained('language_mufradat_schedules')->nullOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('mustami_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('setoran_date');
            $table->unsignedSmallInteger('total_words')->default(0);
            $table->unsignedSmallInteger('correct_words')->default(0);
            $table->decimal('score', 5, 2)->default(0);
            $table->string('status', 20)->default('lulus');
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['period_id', 'student_id', 'setoran_date', 'schedule_id'], 'lang_setoran_period_student_date_schedule_unique');
            $table->index(['period_id', 'setoran_date']);
        });

        Schema::create('language_violations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('violation_date');
            $table->string('place', 150)->nullable();
            $table->string('class_name', 100)->nullable();
            $table->foreignUuid('dormitory_id')->nullable()->constrained('dormitories')->nullOnDelete();
            $table->string('violation_type', 120);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('points')->default(1);
            $table->string('status', 20)->default('recorded');
            $table->foreignUuid('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'violation_date']);
            $table->index(['student_id', 'violation_date']);
        });

        Schema::create('language_coachings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('violation_id')->constrained('language_violations')->cascadeOnDelete();
            $table->string('category', 120)->nullable();
            $table->text('action')->nullable();
            $table->date('coaching_date')->nullable();
            $table->foreignUuid('coach_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->text('follow_up')->nullable();
            $table->boolean('pledge_signed')->default(false);
            $table->timestamps();

            $table->index(['violation_id', 'coaching_date']);
        });

        Schema::create('language_monthly_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->date('period_month');
            $table->string('title', 180);
            $table->string('theme', 180)->nullable();
            $table->string('activity_type', 40);
            $table->date('activity_date')->nullable();
            $table->time('time_start')->nullable();
            $table->time('time_end')->nullable();
            $table->string('place', 150)->nullable();
            $table->string('executor_type', 30)->default('santri');
            $table->string('executor_name', 180)->nullable();
            $table->string('mc_name', 180)->nullable();
            $table->text('committee')->nullable();
            $table->unsignedInteger('participants_count')->default(0);
            $table->text('participation_notes')->nullable();
            $table->text('report')->nullable();
            $table->string('status', 20)->default('planned');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'period_month']);
            $table->index(['activity_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('language_monthly_activities');
        Schema::dropIfExists('language_coachings');
        Schema::dropIfExists('language_violations');
        Schema::dropIfExists('language_mufradat_setorans');
        Schema::dropIfExists('language_mufradat_schedules');
        Schema::dropIfExists('language_mustami_assignments');
        Schema::dropIfExists('language_vocabularies');
        Schema::dropIfExists('language_vocabulary_periods');
    }
};
