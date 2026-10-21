<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('humas_satisfaction_surveys')) {
            Schema::create('humas_satisfaction_surveys', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('audience', 20)->default('all');
                $table->string('status', 20)->default('draft');
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('humas_satisfaction_responses')) {
            Schema::create('humas_satisfaction_responses', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('humas_satisfaction_survey_id')->constrained('humas_satisfaction_surveys')->cascadeOnDelete();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedTinyInteger('rating');
                $table->text('feedback')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();
                $table->unique(['humas_satisfaction_survey_id', 'user_id'], 'humas_survey_user_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('humas_satisfaction_responses');
        Schema::dropIfExists('humas_satisfaction_surveys');
    }
};
