<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('curriculum_projects')) {
            Schema::create('curriculum_projects', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->string('semester', 10)->default('ganjil');
                $table->uuid('curriculum_profile_id')->nullable();
                $table->string('type', 10)->default('p5')->comment('p5 / p5ra');
                $table->string('title');
                $table->string('theme')->nullable();
                $table->json('dimensions')->nullable()->comment('Kode nilai/dimensi yang disasar');
                $table->json('grade_level_ids')->nullable();
                $table->text('description')->nullable();
                $table->json('activities')->nullable()->comment('Rangkaian aktivitas projek');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->uuid('coordinator_id')->nullable();
                $table->string('status', 20)->default('draft')->comment('draft / active / completed');
                $table->text('notes')->nullable();
                $table->uuid('created_by')->nullable();
                $table->timestamps();

                $table->foreign('curriculum_profile_id')->references('id')->on('curriculum_profiles')->nullOnDelete();
                $table->foreign('coordinator_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['school_id', 'academic_year_id', 'type', 'status'], 'curriculum_projects_context_idx');
            });
        }

        if (Schema::hasTable('ai_generated_contents') && ! Schema::hasColumn('ai_generated_contents', 'curriculum_profile_id')) {
            Schema::table('ai_generated_contents', function (Blueprint $table) {
                $table->uuid('curriculum_profile_id')->nullable()->after('academic_year_id');
                $table->foreign('curriculum_profile_id')->references('id')->on('curriculum_profiles')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ai_generated_contents') && Schema::hasColumn('ai_generated_contents', 'curriculum_profile_id')) {
            Schema::table('ai_generated_contents', function (Blueprint $table) {
                $table->dropForeign(['curriculum_profile_id']);
                $table->dropColumn('curriculum_profile_id');
            });
        }

        Schema::dropIfExists('curriculum_projects');
    }
};
