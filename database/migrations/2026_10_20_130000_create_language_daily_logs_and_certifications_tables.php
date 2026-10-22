<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('language_daily_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->date('log_date');
            $table->string('log_type', 30)->default('percakapan');
            $table->string('theme', 180)->nullable();
            $table->string('location', 150)->nullable();
            $table->string('class_name', 100)->nullable();
            $table->foreignUuid('mustami_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('participants_count')->default(0);
            $table->text('findings')->nullable();
            $table->text('follow_up')->nullable();
            $table->string('status', 20)->default('selesai');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'log_type', 'log_date']);
        });

        Schema::create('language_certifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('certification_name', 180);
            $table->string('language', 30)->default('arab');
            $table->string('level', 60)->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->date('issued_date')->nullable();
            $table->string('certificate_number', 100)->nullable();
            $table->string('status', 20)->default('issued');
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'language', 'issued_date']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('language_certifications');
        Schema::dropIfExists('language_daily_logs');
    }
};
