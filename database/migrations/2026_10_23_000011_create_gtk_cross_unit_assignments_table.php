<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gtk_cross_unit_assignments')) {
            Schema::create('gtk_cross_unit_assignments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->uuid('home_work_unit_id');
                $table->uuid('school_id');
                $table->uuid('academic_year_id');
                $table->uuid('subject_id')->nullable();
                $table->string('decree_number')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('status')->default('active');
                $table->text('notes')->nullable();
                $table->uuid('created_by')->nullable();
                $table->uuid('approved_by')->nullable();
                $table->timestamps();

                $table->index(['school_id', 'academic_year_id', 'status'], 'gtk_cross_unit_school_year_status_idx');
                $table->index(['user_id', 'status'], 'gtk_cross_unit_user_status_idx');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('home_work_unit_id')->references('id')->on('work_units')->cascadeOnDelete();
                $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
                $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
                $table->foreign('subject_id')->references('id')->on('subjects')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('teaching_assignments', 'assignment_source')) {
            Schema::table('teaching_assignments', function (Blueprint $table) {
                $table->string('assignment_source')->default('unit')->after('status');
                $table->uuid('gtk_cross_unit_assignment_id')->nullable()->after('assignment_source');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('teaching_assignments', 'assignment_source')) {
            Schema::table('teaching_assignments', function (Blueprint $table) {
                $table->dropColumn(['assignment_source', 'gtk_cross_unit_assignment_id']);
            });
        }

        Schema::dropIfExists('gtk_cross_unit_assignments');
    }
};
