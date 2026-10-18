<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tahfidz_interventions')) {
            return;
        }

        Schema::create('tahfidz_interventions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('tahfidz_group_id')->nullable();
            $table->uuid('academic_year_id');
            $table->string('type', 50)->default('bimbingan_tambahan');
            $table->string('title', 150)->nullable();
            $table->text('notes')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('open');
            $table->uuid('assigned_to')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('tahfidz_group_id')->references('id')->on('tahfidz_groups')->nullOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['student_id', 'academic_year_id', 'status'], 'idx_tahfidz_intervention_student');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_interventions');
    }
};
