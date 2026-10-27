<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_feedback_reports')) {
            Schema::create('app_feedback_reports', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
                $table->foreignUuid('work_unit_id')->nullable()->constrained('work_units')->nullOnDelete();
                $table->foreignUuid('submitted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('category', 30)->default('bug');
                $table->string('priority', 20)->default('sedang');
                $table->string('status', 20)->default('baru');
                $table->string('title');
                $table->text('description');
                $table->text('process')->nullable();
                $table->text('expected')->nullable();
                $table->string('page_url', 2048)->nullable();
                $table->string('division', 120)->nullable();
                $table->string('jabatan', 120)->nullable();
                $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'category']);
                $table->index(['school_id', 'created_at']);
                $table->index(['work_unit_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('app_feedback_updates')) {
            Schema::create('app_feedback_updates', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('app_feedback_report_id')->constrained('app_feedback_reports')->cascadeOnDelete();
                $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('type', 20)->default('comment');
                $table->string('status_from', 20)->nullable();
                $table->string('status_to', 20)->nullable();
                $table->text('message')->nullable();
                $table->boolean('is_internal')->default(false);
                $table->timestamps();

                $table->index(['app_feedback_report_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('app_feedback_attachments')) {
            Schema::create('app_feedback_attachments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('app_feedback_report_id')->constrained('app_feedback_reports')->cascadeOnDelete();
                $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('path');
                $table->string('original_name');
                $table->string('mime_type', 150)->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->timestamps();

                $table->index('app_feedback_report_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_feedback_attachments');
        Schema::dropIfExists('app_feedback_updates');
        Schema::dropIfExists('app_feedback_reports');
    }
};
