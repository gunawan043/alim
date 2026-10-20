<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('curriculum_project_assessments')) {
            Schema::create('curriculum_project_assessments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('project_id')->constrained('curriculum_projects')->cascadeOnDelete();
                $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
                $table->json('scores')->nullable()->comment('Skor 1-4 per dimensi/nilai');
                $table->decimal('average', 4, 2)->default(0);
                $table->string('predicate', 40)->nullable();
                $table->text('notes')->nullable();
                $table->uuid('assessed_by')->nullable();
                $table->timestamps();

                $table->foreign('assessed_by')->references('id')->on('users')->nullOnDelete();
                $table->unique(['project_id', 'student_id'], 'project_student_assessment_unique');
                $table->index(['project_id', 'average']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_project_assessments');
    }
};
