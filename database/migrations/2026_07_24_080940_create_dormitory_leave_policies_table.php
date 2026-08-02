<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-dormitory configuration for leave permits:
     *   - which permit_types are enabled
     *   - quota per period (weekly / monthly / yearly / semester)
     *   - emergency settings (bypass quota, who can approve, WA notify)
     *
     * One row per (dormitory_id, permit_type). Dormitory-wide defaults live
     * in the row where permit_type = '__default__' (created on first save if
     * missing). Updating "Apply to all permit_types" from the UI upserts the
     * default row.
     */
    public function up(): void
    {
        Schema::create('dormitory_leave_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('dormitory_id');
            $table->foreign('dormitory_id')->references('id')->on('dormitories')->cascadeOnDelete();

            // '__default__' means "apply to every permit_type until overridden".
            $table->string('permit_type', 50);

            $table->boolean('is_enabled')->default(true);
            $table->boolean('requires_approval')->default(true);

            // Quota across nested periods (most specific wins; null = no limit).
            $table->unsignedSmallInteger('quota_per_week')->nullable();
            $table->unsignedSmallInteger('quota_per_month')->nullable();
            $table->unsignedSmallInteger('quota_per_semester')->nullable();
            $table->unsignedSmallInteger('quota_per_year')->nullable();

            // Auto-approve: skip approver gate when submitted by certain roles.
            $table->boolean('auto_approve_gtk')->default(false)
                ->comment('If true, permits submitted by GTK skip approval gate.');
            $table->boolean('auto_approve_kepala_asrama')->default(false);

            // Emergency-specific settings (only meaningful when permit_type = 'darurat').
            $table->boolean('emergency_bypass_quota')->default(false)
                ->comment('If true, emergency permits bypass the quota check.');
            $table->boolean('emergency_notify_wa_kepala')->default(false)
                ->comment('If true, send WhatsApp to kepala_asrama when emergency submitted.');
            $table->json('emergency_approver_roles')->nullable()
                ->comment('Roles allowed to approve emergency permits. e.g. ["kepala_asrama","admin_asrama"].');

            $table->uuid('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['dormitory_id', 'permit_type'], 'dlp_dorm_type_unique');
            $table->index('dormitory_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_leave_policies');
    }
};
