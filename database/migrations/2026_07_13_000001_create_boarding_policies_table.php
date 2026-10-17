<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boarding_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();

            $table->enum('leave_strategy', ['quota', 'unrestricted', 'banned'])
                ->default('quota')
                ->comment('How leave is governed: quota-based, unrestricted, or banned.');

            $table->unsignedInteger('leave_quota')->nullable()
                ->comment('Maximum leave count per quota period. NULL = unrestricted.');

            $table->enum('leave_quota_period', ['weekly', 'monthly', 'semester', 'yearly'])
                ->default('monthly');

            $table->enum('visit_strategy', ['quota', 'unrestricted', 'banned'])
                ->default('unrestricted');

            $table->unsignedInteger('visit_quota')->nullable();
            $table->enum('visit_quota_period', ['daily', 'weekly', 'monthly'])
                ->default('monthly');
            $table->unsignedInteger('max_visitors_per_visit')->nullable();

            $table->unsignedInteger('curfew_hour')->nullable()
                ->comment('Hour in 24h. NULL = no curfew enforced.');

            $table->boolean('special_permission_allowed')->default(true);
            $table->json('special_permission_types')->nullable()
                ->comment('Override types: medical, emergency, family, competition, school_activity.');

            $table->boolean('auto_sync_academic_attendance')->default(true);
            $table->boolean('is_active')->default(true);

            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'leave_strategy']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boarding_policies');
    }
};
