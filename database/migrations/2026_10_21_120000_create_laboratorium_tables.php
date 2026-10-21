<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lab_profiles')) {
            Schema::create('lab_profiles', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignUuid('work_unit_id')->nullable()->constrained('work_units')->nullOnDelete();
                $table->foreignUuid('asset_room_id')->nullable()->unique()->constrained('asset_rooms')->nullOnDelete();
                $table->string('code', 30);
                $table->string('name', 150);
                $table->string('lab_type', 30)->default('ipa');
                $table->foreignUuid('coordinator_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('head_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['school_id', 'code'], 'lab_profiles_school_code_unique');
                $table->index(['school_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('lab_item_profiles')) {
            Schema::create('lab_item_profiles', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('lab_id')->constrained('lab_profiles')->cascadeOnDelete();
                $table->foreignUuid('asset_id')->unique()->constrained('assets')->cascadeOnDelete();
                $table->string('item_kind', 30)->default('alat');
                $table->boolean('requires_calibration')->default(false);
                $table->unsignedSmallInteger('calibration_interval_months')->default(12);
                $table->unsignedInteger('min_stock')->default(0);
                $table->string('storage_location', 150)->nullable();
                $table->string('hazard_class', 100)->nullable();
                $table->boolean('is_consumable')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['lab_id', 'item_kind']);
            });
        }

        if (! Schema::hasTable('lab_item_batches')) {
            Schema::create('lab_item_batches', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('lab_id')->constrained('lab_profiles')->cascadeOnDelete();
                $table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();
                $table->string('batch_code', 80)->nullable();
                $table->unsignedInteger('quantity')->default(0);
                $table->string('unit', 30)->default('unit');
                $table->date('received_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['asset_id', 'expiry_date']);
            });
        }

        if (! Schema::hasTable('lab_stock_movements')) {
            Schema::create('lab_stock_movements', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('lab_id')->constrained('lab_profiles')->cascadeOnDelete();
                $table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();
                $table->foreignUuid('batch_id')->nullable()->constrained('lab_item_batches')->nullOnDelete();
                $table->string('movement_type', 30);
                $table->integer('quantity');
                $table->string('unit', 30)->default('unit');
                $table->string('reference_type', 100)->nullable();
                $table->uuid('reference_id')->nullable();
                $table->text('notes')->nullable();
                $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('occurred_at')->useCurrent();
                $table->timestamps();
                $table->index(['lab_id', 'occurred_at']);
                $table->index(['asset_id', 'movement_type']);
            });
        }

        if (! Schema::hasTable('lab_condition_histories')) {
            Schema::create('lab_condition_histories', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('lab_id')->constrained('lab_profiles')->cascadeOnDelete();
                $table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();
                $table->string('condition_code', 2);
                $table->string('previous_code', 2)->nullable();
                $table->string('source_type', 100)->nullable();
                $table->uuid('source_id')->nullable();
                $table->text('notes')->nullable();
                $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('occurred_at')->useCurrent();
                $table->timestamps();
                $table->index(['asset_id', 'occurred_at']);
            });
        }

        if (! Schema::hasTable('lab_inspections')) {
            Schema::create('lab_inspections', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('lab_id')->constrained('lab_profiles')->cascadeOnDelete();
                $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->unsignedTinyInteger('semester')->default(1);
                $table->date('scheduled_date')->nullable();
                $table->date('performed_date')->nullable();
                $table->foreignUuid('inspector_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 30)->default('scheduled');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['lab_id', 'scheduled_date', 'status']);
            });
        }

        if (! Schema::hasTable('lab_inspection_items')) {
            Schema::create('lab_inspection_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('inspection_id')->constrained('lab_inspections')->cascadeOnDelete();
                $table->foreignUuid('asset_id')->nullable()->constrained('assets')->nullOnDelete();
                $table->string('item_name', 191);
                $table->unsignedInteger('quantity')->default(1);
                $table->string('condition_code', 2);
                $table->string('maintenance_action', 191)->nullable();
                $table->string('follow_up_type', 30)->nullable();
                $table->text('follow_up')->nullable();
                $table->date('inspected_at')->nullable();
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
                $table->index(['inspection_id', 'sort']);
            });
        }

        if (! Schema::hasTable('lab_calibrations')) {
            Schema::create('lab_calibrations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('lab_id')->constrained('lab_profiles')->cascadeOnDelete();
                $table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();
                $table->date('last_calibration_date')->nullable();
                $table->date('next_calibration_date')->nullable();
                $table->string('calibration_type', 1)->default('I');
                $table->string('status', 2)->default('OS');
                $table->foreignUuid('performed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('institution_name', 191)->nullable();
                $table->string('certificate_path', 255)->nullable();
                $table->text('result')->nullable();
                $table->text('notes')->nullable();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['asset_id', 'next_calibration_date']);
                $table->index(['lab_id', 'status']);
            });
        }

        if (! Schema::hasTable('lab_loans')) {
            Schema::create('lab_loans', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('lab_id')->constrained('lab_profiles')->cascadeOnDelete();
                $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->unsignedTinyInteger('semester')->default(1);
                $table->foreignUuid('borrower_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('borrower_name', 191);
                $table->string('institution', 191)->nullable();
                $table->date('loan_date');
                $table->text('purpose');
                $table->date('planned_return_date')->nullable();
                $table->date('actual_return_date')->nullable();
                $table->string('status', 40)->default('submitted');
                $table->foreignUuid('handed_over_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('received_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('notes')->nullable();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['lab_id', 'loan_date', 'status']);
            });
        }

        if (! Schema::hasTable('lab_loan_items')) {
            Schema::create('lab_loan_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('loan_id')->constrained('lab_loans')->cascadeOnDelete();
                $table->foreignUuid('asset_id')->nullable()->constrained('assets')->nullOnDelete();
                $table->string('item_name', 191);
                $table->unsignedInteger('quantity')->default(1);
                $table->string('unit', 30)->default('unit');
                $table->string('condition_on_loan', 30)->default('baik');
                $table->string('condition_on_return', 30)->nullable();
                $table->unsignedInteger('quantity_returned')->default(0);
                $table->text('damage_notes')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['loan_id']);
            });
        }

        if (! Schema::hasTable('lab_activities')) {
            Schema::create('lab_activities', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('lab_id')->constrained('lab_profiles')->cascadeOnDelete();
                $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->unsignedTinyInteger('semester')->default(1);
                $table->foreignUuid('teacher_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
                $table->foreignUuid('study_group_id')->nullable()->constrained('study_groups')->nullOnDelete();
                $table->string('title', 191);
                $table->date('activity_date');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->unsignedInteger('participants_count')->default(0);
                $table->string('status', 30)->default('draft');
                $table->text('risk_notes')->nullable();
                $table->text('notes')->nullable();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['lab_id', 'activity_date', 'status']);
            });
        }

        if (! Schema::hasTable('lab_activity_items')) {
            Schema::create('lab_activity_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('activity_id')->constrained('lab_activities')->cascadeOnDelete();
                $table->foreignUuid('asset_id')->nullable()->constrained('assets')->nullOnDelete();
                $table->string('item_name', 191);
                $table->unsignedInteger('quantity')->default(1);
                $table->string('unit', 30)->default('unit');
                $table->string('readiness_status', 30)->default('belum');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['activity_id']);
            });
        }

        if (! Schema::hasTable('lab_activity_attendances')) {
            Schema::create('lab_activity_attendances', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('activity_id')->constrained('lab_activities')->cascadeOnDelete();
                $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
                $table->string('status', 20)->default('hadir');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['activity_id', 'student_id'], 'lab_activity_student_unique');
            });
        }

        if (! Schema::hasTable('lab_usage_requests')) {
            Schema::create('lab_usage_requests', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('lab_id')->constrained('lab_profiles')->cascadeOnDelete();
                $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->unsignedTinyInteger('semester')->default(1);
                $table->foreignUuid('applicant_id')->constrained('users')->cascadeOnDelete();
                $table->string('applicant_position_class', 191)->nullable();
                $table->foreignUuid('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
                $table->date('usage_date');
                $table->string('activity_type', 30)->default('praktikum');
                $table->string('other_activity', 191)->nullable();
                $table->unsignedInteger('participants_count')->default(0);
                $table->text('risk_notes')->nullable();
                $table->string('status', 30)->default('submitted');
                $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->foreignUuid('activity_id')->nullable()->constrained('lab_activities')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['lab_id', 'usage_date', 'status']);
            });
        }

        if (! Schema::hasTable('lab_schedule_slots')) {
            Schema::create('lab_schedule_slots', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('lab_id')->constrained('lab_profiles')->cascadeOnDelete();
                $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->unsignedTinyInteger('semester')->default(1);
                $table->unsignedTinyInteger('day_of_week');
                $table->unsignedSmallInteger('slot_index');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->foreignUuid('study_group_id')->nullable()->constrained('study_groups')->nullOnDelete();
                $table->foreignUuid('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
                $table->foreignUuid('teacher_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('usage_request_id')->nullable()->constrained('lab_usage_requests')->nullOnDelete();
                $table->string('source', 20)->default('manual');
                $table->foreignUuid('source_jadwal_kbm_id')->nullable()->constrained('jadwal_kbms')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['lab_id', 'academic_year_id', 'semester', 'day_of_week', 'slot_index'], 'lab_schedule_unique_slot');
                $table->index(['lab_id', 'academic_year_id', 'semester']);
            });
        }

        if (! Schema::hasTable('lab_reports')) {
            Schema::create('lab_reports', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('activity_id')->constrained('lab_activities')->cascadeOnDelete();
                $table->date('report_date');
                $table->time('time_start')->nullable();
                $table->time('time_end')->nullable();
                $table->string('title', 191);
                $table->string('subject_name', 191)->nullable();
                $table->string('class_name', 100)->nullable();
                $table->unsignedInteger('participants_total')->default(0);
                $table->unsignedInteger('participants_present')->default(0);
                $table->unsignedInteger('participants_absent')->default(0);
                $table->json('absent_names')->nullable();
                $table->text('notes')->nullable();
                $table->foreignUuid('teacher_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->string('status', 30)->default('draft');
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['activity_id', 'status']);
            });
        }

        if (! Schema::hasTable('lab_safety_inspections')) {
            Schema::create('lab_safety_inspections', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('lab_id')->constrained('lab_profiles')->cascadeOnDelete();
                $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->unsignedTinyInteger('semester')->default(1);
                $table->date('inspection_date');
                $table->foreignUuid('inspector_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('inspector_position', 191)->nullable();
                $table->string('status', 30)->default('submitted');
                $table->text('findings')->nullable();
                $table->text('recommendations')->nullable();
                $table->date('follow_up_date')->nullable();
                $table->text('follow_up_action')->nullable();
                $table->string('follow_up_person', 191)->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['lab_id', 'inspection_date']);
            });
        }

        if (! Schema::hasTable('lab_safety_inspection_items')) {
            Schema::create('lab_safety_inspection_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('inspection_id')->constrained('lab_safety_inspections')->cascadeOnDelete();
                $table->unsignedTinyInteger('source_no');
                $table->string('aspect', 191);
                $table->string('status', 20)->default('Baik');
                $table->text('notes')->nullable();
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
                $table->unique(['inspection_id', 'source_no'], 'lab_safety_inspection_source_no_unique');
            });
        }

        if (! Schema::hasTable('lab_incidents')) {
            Schema::create('lab_incidents', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('lab_id')->constrained('lab_profiles')->cascadeOnDelete();
                $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
                $table->date('incident_date');
                $table->time('incident_time')->nullable();
                $table->foreignUuid('reporter_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reporter_position', 191)->nullable();
                $table->string('incident_type', 40);
                $table->string('other_type', 191)->nullable();
                $table->text('chronology');
                $table->string('location', 191)->nullable();
                $table->string('victim_name', 191)->nullable();
                $table->string('injury_type', 191)->nullable();
                $table->text('first_aid')->nullable();
                $table->text('equipment_involved')->nullable();
                $table->text('immediate_action')->nullable();
                $table->text('prevention')->nullable();
                $table->text('personnel')->nullable();
                $table->string('status', 30)->default('reported');
                $table->text('follow_up')->nullable();
                $table->boolean('is_confidential')->default(true);
                $table->uuid('uks_referral_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['lab_id', 'incident_date', 'status']);
            });
        }

        if (! Schema::hasTable('lab_disposals')) {
            Schema::create('lab_disposals', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('lab_id')->constrained('lab_profiles')->cascadeOnDelete();
                $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->unsignedTinyInteger('semester')->default(1);
                $table->string('disposal_number', 100);
                $table->foreignUuid('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->date('disposal_date')->nullable();
                $table->text('follow_up')->nullable();
                $table->string('status', 30)->default('draft');
                $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['lab_id', 'status']);
            });
        }

        if (! Schema::hasTable('lab_disposal_items')) {
            Schema::create('lab_disposal_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('disposal_id')->constrained('lab_disposals')->cascadeOnDelete();
                $table->foreignUuid('asset_id')->nullable()->constrained('assets')->nullOnDelete();
                $table->string('item_name', 191);
                $table->unsignedInteger('quantity')->default(1);
                $table->string('unit', 30)->default('unit');
                $table->string('condition_code', 2)->nullable();
                $table->text('reason');
                $table->string('method', 100);
                $table->text('notes')->nullable();
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
                $table->index(['disposal_id', 'sort']);
            });
        }

        if (! Schema::hasTable('lab_evaluations')) {
            Schema::create('lab_evaluations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('lab_id')->constrained('lab_profiles')->cascadeOnDelete();
                $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->unsignedTinyInteger('semester')->default(1);
                $table->date('evaluation_date');
                $table->text('findings');
                $table->text('recommendations')->nullable();
                $table->text('follow_up')->nullable();
                $table->foreignUuid('evaluated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 30)->default('draft');
                $table->timestamps();
                $table->softDeletes();
                $table->index(['lab_id', 'academic_year_id', 'semester']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_evaluations');
        Schema::dropIfExists('lab_disposal_items');
        Schema::dropIfExists('lab_disposals');
        Schema::dropIfExists('lab_incidents');
        Schema::dropIfExists('lab_safety_inspection_items');
        Schema::dropIfExists('lab_safety_inspections');
        Schema::dropIfExists('lab_reports');
        Schema::dropIfExists('lab_schedule_slots');
        Schema::dropIfExists('lab_usage_requests');
        Schema::dropIfExists('lab_activity_attendances');
        Schema::dropIfExists('lab_activity_items');
        Schema::dropIfExists('lab_activities');
        Schema::dropIfExists('lab_loan_items');
        Schema::dropIfExists('lab_loans');
        Schema::dropIfExists('lab_calibrations');
        Schema::dropIfExists('lab_inspection_items');
        Schema::dropIfExists('lab_inspections');
        Schema::dropIfExists('lab_condition_histories');
        Schema::dropIfExists('lab_stock_movements');
        Schema::dropIfExists('lab_item_batches');
        Schema::dropIfExists('lab_item_profiles');
        Schema::dropIfExists('lab_profiles');
    }
};
