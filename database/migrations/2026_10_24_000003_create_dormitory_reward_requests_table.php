<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dormitory_reward_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_achievement_id')->nullable()->unique();
            $table->uuid('student_id');
            $table->uuid('dormitory_id');
            $table->uuid('academic_year_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 50);
            $table->string('level', 50);
            $table->uuid('point_catalog_id')->nullable();
            $table->integer('points')->default(0);
            $table->string('status', 20)->default('pending')->index();
            $table->boolean('notify_approvers')->default(true);
            $table->uuid('requested_by')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->uuid('dormitory_reward_id')->nullable()->unique();
            $table->json('source_snapshot')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('dormitory_id')->references('id')->on('dormitories')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
            $table->foreign('point_catalog_id')->references('id')->on('dormitory_point_catalogs')->nullOnDelete();
            $table->foreign('requested_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('dormitory_reward_id')->references('id')->on('dormitory_rewards')->nullOnDelete();

            $table->index(['dormitory_id', 'status']);
            $table->index(['student_id', 'status']);
        });

        if (Schema::hasTable('dormitory_rewards')
            && ! Schema::hasColumn('dormitory_rewards', 'source_achievement_id')) {
            Schema::table('dormitory_rewards', function (Blueprint $table) {
                $table->uuid('source_achievement_id')->nullable()->index();
                $table->string('source_label')->nullable();
                $table->timestamp('source_deleted_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dormitory_rewards')
            && Schema::hasColumn('dormitory_rewards', 'source_achievement_id')) {
            Schema::table('dormitory_rewards', function (Blueprint $table) {
                $table->dropIndex(['source_achievement_id']);
                $table->dropColumn(['source_achievement_id', 'source_label', 'source_deleted_at']);
            });
        }

        Schema::dropIfExists('dormitory_reward_requests');
    }
};
