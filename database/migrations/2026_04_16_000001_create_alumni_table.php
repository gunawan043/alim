<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('school_id');
            $table->unsignedSmallInteger('graduation_year');
            $table->string('graduation_certificate_number', 50)->nullable();
            $table->date('graduation_date')->nullable();

            // Tracer Study - Continuer
            $table->enum('continuing_study_status', ['belum', 'sedang', 'sudah'])->default('belum');
            $table->string('higher_education_institution')->nullable();
            $table->string('study_program')->nullable();
            $table->string('higher_education_city')->nullable();
            $table->year('higher_education_year_start')->nullable();

            // Tracer Study - Working
            $table->enum('working_status', ['belum', 'sedang', 'sudah'])->default('belum');
            $table->string('occupation')->nullable();
            $table->string('company_name')->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_phone', 20)->nullable();
            $table->string('company_city')->nullable();
            $table->decimal('monthly_income', 12, 0)->nullable();
            $table->year('working_year_start')->nullable();

            // Tracer Study - Other
            $table->string('further_study_institution')->nullable();
            $table->string('further_study_program')->nullable();

            // Contact & Status
            $table->boolean('is_contactable')->default(true);
            $table->date('last_contact_date')->nullable();
            $table->text('achievements')->nullable();
            $table->text('tracer_notes')->nullable();
            $table->enum('tracer_status', ['pending', 'filled', 'verified'])->default('pending');
            $table->timestamp('tracer_filled_at')->nullable();

            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');

            $table->unique(['student_id']);
            $table->index(['school_id', 'graduation_year']);
            $table->index('tracer_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni');
    }
};
