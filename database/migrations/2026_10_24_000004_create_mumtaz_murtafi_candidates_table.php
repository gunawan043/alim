<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mumtaz_murtafi_candidates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('school_id');
            $table->uuid('study_group_id')->nullable();
            $table->uuid('academic_year_id');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->decimal('average_score', 5, 2)->nullable();
            $table->unsignedSmallInteger('class_rank')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->uuid('student_achievement_id')->nullable();
            $table->uuid('generated_by')->nullable();
            $table->uuid('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('study_group_id')->references('id')->on('study_groups')->nullOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('student_achievement_id')->references('id')->on('student_achievements')->nullOnDelete();
            $table->foreign('generated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('posted_by')->references('id')->on('users')->nullOnDelete();

            $table->unique(
                ['student_id', 'academic_year_id', 'semester'],
                'unique_mumtaz_candidate_per_student_per_term'
            );
            $table->index(['school_id', 'status'], 'idx_mumtaz_candidate_school_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mumtaz_murtafi_candidates');
    }
};
