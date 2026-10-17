<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V10 — Draft AI untuk RPM & soal.
 *
 * Menyimpan hasil generasi AI (JSON + raw) beserta status review, agar dapat
 * ditinjau/disetujui sebelum dipakai pada modul resmi (RPM / Bank Soal).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_generated_contents')) {
            Schema::create('ai_generated_contents', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
                $table->foreignUuid('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
                $table->string('semester', 10)->nullable();
                $table->string('type', 10)->comment('rpm|soal');
                $table->foreignUuid('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
                $table->foreignUuid('grade_level_id')->nullable()->constrained('grade_levels')->nullOnDelete();
                $table->string('title');
                $table->text('topik')->nullable();
                $table->json('content')->nullable();
                $table->longText('raw_content')->nullable();
                $table->string('status', 20)->default('draft')->comment('draft|approved');
                $table->string('provider', 100)->nullable();
                $table->string('model', 150)->nullable();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                $table->index(['school_id', 'type', 'status'], 'ai_generated_school_type_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generated_contents');
    }
};
