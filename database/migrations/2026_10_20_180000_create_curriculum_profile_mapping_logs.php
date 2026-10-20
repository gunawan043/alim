<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('curriculum_profile_mapping_logs')) {
            Schema::create('curriculum_profile_mapping_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('batch_id');
                $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->uuid('old_profile_id')->nullable();
                $table->uuid('new_profile_id')->nullable();
                $table->string('old_kokurikuler_type', 30)->nullable();
                $table->string('new_kokurikuler_type', 30)->nullable();
                $table->boolean('old_setting_exists')->default(true);
                $table->uuid('changed_by')->nullable();
                $table->timestamp('rolled_back_at')->nullable();
                $table->uuid('rolled_back_by')->nullable();
                $table->timestamps();

                $table->foreign('old_profile_id')->references('id')->on('curriculum_profiles')->nullOnDelete();
                $table->foreign('new_profile_id')->references('id')->on('curriculum_profiles')->nullOnDelete();
                $table->foreign('changed_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('rolled_back_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['batch_id', 'rolled_back_at'], 'curriculum_mapping_batch_idx');
                $table->index(['academic_year_id', 'created_at'], 'curriculum_mapping_year_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_profile_mapping_logs');
    }
};
