<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('library_units')) {
            Schema::create('library_units', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('work_unit_id')->nullable()->constrained('work_units')->nullOnDelete();
                $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
                $table->string('code', 30)->unique();
                $table->string('name');
                $table->string('gender_scope', 10)->default('mixed');
                $table->string('level_scope', 20)->default('sd_smp');
                $table->string('location')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('library_staff')) {
            Schema::create('library_staff', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('library_unit_id')->constrained('library_units')->cascadeOnDelete();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role', 30)->default('staf');
                $table->boolean('is_active')->default(true);
                $table->date('started_at')->nullable();
                $table->date('ended_at')->nullable();
                $table->timestamps();
                $table->unique(['library_unit_id', 'user_id'], 'library_staff_unit_user_unique');
            });
        }

        if (! Schema::hasTable('library_settings')) {
            Schema::create('library_settings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('library_unit_id')->constrained('library_units')->cascadeOnDelete();
                $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->unsignedSmallInteger('loan_days')->default(7);
                $table->unsignedSmallInteger('max_active_loans')->default(3);
                $table->boolean('allow_renewal')->default(true);
                $table->unsignedSmallInteger('max_renewals')->default(1);
                $table->unsignedSmallInteger('renewal_days')->default(7);
                $table->boolean('allow_fine')->default(false);
                $table->decimal('fine_per_day', 12, 2)->default(0);
                $table->decimal('max_fine', 12, 2)->nullable();
                $table->decimal('replacement_card_fee', 12, 2)->default(0);
                $table->timestamps();
                $table->unique(['library_unit_id', 'academic_year_id'], 'library_settings_unit_year_unique');
            });
        }

        if (! Schema::hasTable('library_genres')) {
            Schema::create('library_genres', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
                $table->string('code', 30)->nullable();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('library_titles')) {
            Schema::create('library_titles', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
                $table->foreignUuid('genre_id')->nullable()->constrained('library_genres')->nullOnDelete();
                $table->string('title');
                $table->string('author')->nullable();
                $table->string('publisher')->nullable();
                $table->year('publication_year')->nullable();
                $table->string('isbn', 30)->nullable();
                $table->text('description')->nullable();
                $table->string('cover_path')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('library_items')) {
            Schema::create('library_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('library_title_id')->constrained('library_titles')->cascadeOnDelete();
                $table->foreignUuid('library_unit_id')->constrained('library_units')->cascadeOnDelete();
                $table->string('inventory_code', 80)->unique();
                $table->string('shelf_location')->nullable();
                $table->string('condition', 30)->default('baik');
                $table->string('status', 30)->default('available');
                $table->date('acquisition_date')->nullable();
                $table->decimal('price', 12, 2)->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('qr_generated_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['library_unit_id', 'status']);
            });
        }

        if (! Schema::hasTable('library_members')) {
            Schema::create('library_members', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('library_unit_id')->constrained('library_units')->cascadeOnDelete();
                $table->foreignUuid('student_id')->nullable()->constrained('students')->cascadeOnDelete();
                $table->foreignUuid('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->string('member_number', 50)->unique();
                $table->string('member_type', 20)->default('student');
                $table->string('status', 30)->default('active');
                $table->date('joined_at')->nullable();
                $table->date('ended_at')->nullable();
                $table->boolean('is_primary')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['library_unit_id', 'student_id'], 'library_member_unit_student_unique');
                $table->unique(['library_unit_id', 'user_id'], 'library_member_unit_user_unique');
            });
        }

        if (! Schema::hasTable('library_member_cards')) {
            Schema::create('library_member_cards', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('library_member_id')->constrained('library_members')->cascadeOnDelete();
                $table->string('card_number', 50)->unique();
                $table->string('status', 20)->default('active');
                $table->date('issued_at')->nullable();
                $table->date('valid_until')->nullable();
                $table->uuid('replacement_of_id')->nullable();
                $table->decimal('replacement_fee', 12, 2)->default(0);
                $table->string('fee_status', 20)->default('free');
                $table->timestamp('printed_at')->nullable();
                $table->unsignedInteger('print_count')->default(0);
                $table->timestamp('revoked_at')->nullable();
                $table->uuid('revoked_by')->nullable();
                $table->timestamps();
                $table->foreign('replacement_of_id')->references('id')->on('library_member_cards')->nullOnDelete();
                $table->foreign('revoked_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('library_loans')) {
            Schema::create('library_loans', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('library_unit_id')->constrained('library_units')->cascadeOnDelete();
                $table->foreignUuid('library_member_id')->constrained('library_members')->cascadeOnDelete();
                $table->string('loan_number', 60)->unique();
                $table->date('loan_date');
                $table->date('due_date');
                $table->date('returned_at')->nullable();
                $table->string('status', 30)->default('active');
                $table->boolean('physical_card_verified')->default(false);
                $table->timestamp('card_verified_at')->nullable();
                $table->foreignUuid('processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['library_unit_id', 'status', 'due_date']);
            });
        }

        if (! Schema::hasTable('library_loan_items')) {
            Schema::create('library_loan_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('library_loan_id')->constrained('library_loans')->cascadeOnDelete();
                $table->foreignUuid('library_item_id')->constrained('library_items')->cascadeOnDelete();
                $table->date('returned_at')->nullable();
                $table->string('return_condition', 30)->nullable();
                $table->text('return_notes')->nullable();
                $table->string('status', 30)->default('loaned');
                $table->timestamps();
                $table->unique(['library_loan_id', 'library_item_id'], 'library_loan_item_unique');
                $table->index(['library_item_id', 'status']);
            });
        }

        if (! Schema::hasTable('library_visits')) {
            Schema::create('library_visits', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('library_unit_id')->constrained('library_units')->cascadeOnDelete();
                $table->foreignUuid('library_member_id')->nullable()->constrained('library_members')->nullOnDelete();
                $table->string('visitor_name');
                $table->string('visitor_type', 20)->default('student');
                $table->string('class_snapshot')->nullable();
                $table->date('visit_date');
                $table->string('purpose')->nullable();
                $table->foreignUuid('officer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['library_unit_id', 'visit_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('library_visits');
        Schema::dropIfExists('library_loan_items');
        Schema::dropIfExists('library_loans');
        Schema::dropIfExists('library_member_cards');
        Schema::dropIfExists('library_members');
        Schema::dropIfExists('library_items');
        Schema::dropIfExists('library_titles');
        Schema::dropIfExists('library_genres');
        Schema::dropIfExists('library_settings');
        Schema::dropIfExists('library_staff');
        Schema::dropIfExists('library_units');
    }
};
