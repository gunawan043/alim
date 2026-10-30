<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel workspace audit aset (menu QR & Audit Aset → Auditor Workspace).
 *
 * Model + service sudah ada sejak awal (AuditSession, AssetAudit,
 * AuditDiscrepancy, AuditorWorkspaceService) namun migration-nya tidak pernah
 * dibuat sehingga menu "Auditor Workspace" gagal 500 (table not found).
 *
 * Kolom disusun dari gabungan pemakaian nyata:
 *  - App\Services\Sarpras\AuditorWorkspaceService (penulis utama data)
 *  - App\Models\AuditSession / AssetAudit / AuditDiscrepancy (fillable)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_sessions')) {
            Schema::create('audit_sessions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('session_code', 30)->unique();
                $table->uuid('auditor_id');
                $table->string('audit_type', 30)->default('periodic')->comment('periodic, spot_check, penerimaan, mutasi');
                $table->json('scope')->nullable();
                $table->uuid('target_room_id')->nullable();
                $table->uuid('target_category_id')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->string('status', 30)->default('in_progress')->comment('in_progress, completed, cancelled');
                $table->unsignedInteger('assets_audited')->default(0);
                $table->unsignedInteger('discrepancies_found')->default(0);
                $table->json('summary')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('auditor_id')->references('id')->on('users')->cascadeOnDelete();
                $table->index(['status', 'started_at']);
                $table->index('session_code');
            });
        }

        if (! Schema::hasTable('asset_audits')) {
            Schema::create('asset_audits', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('audit_session_id');
                $table->uuid('asset_id');
                $table->uuid('auditor_id');
                $table->string('physical_condition', 30)->nullable();
                $table->boolean('physical_found')->default(true);
                $table->uuid('location_verified')->nullable();
                $table->uuid('expected_location')->nullable();
                $table->uuid('physical_location_id')->nullable();
                $table->boolean('has_discrepancy')->default(false);
                $table->text('condition_notes')->nullable();
                $table->json('photo_urls')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamp('scanned_at')->nullable();
                $table->timestamps();

                $table->foreign('audit_session_id')->references('id')->on('audit_sessions')->cascadeOnDelete();
                $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
                $table->foreign('auditor_id')->references('id')->on('users')->cascadeOnDelete();
                $table->index(['audit_session_id', 'asset_id']);
                $table->index('scanned_at');
            });
        }

        if (! Schema::hasTable('audit_discrepancies')) {
            Schema::create('audit_discrepancies', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('audit_session_id');
                $table->uuid('asset_audit_id')->nullable();
                $table->uuid('asset_id');
                $table->uuid('auditor_id');
                $table->string('discrepancy_type', 40)->comment('not_found, wrong_location, condition_mismatch, missing_component, duplicate_record, ghost_asset');
                $table->string('severity', 20)->default('medium')->comment('low, medium, high, critical');
                $table->text('description')->nullable();
                $table->text('details')->nullable();
                $table->string('expected_value')->nullable();
                $table->string('actual_value')->nullable();
                $table->string('resolution_status', 20)->default('open')->comment('open, resolved, ignored');
                $table->boolean('resolved')->default(false);
                $table->timestamps();

                $table->foreign('audit_session_id')->references('id')->on('audit_sessions')->cascadeOnDelete();
                $table->foreign('asset_audit_id')->references('id')->on('asset_audits')->nullOnDelete();
                $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
                $table->index(['audit_session_id', 'resolution_status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_discrepancies');
        Schema::dropIfExists('asset_audits');
        Schema::dropIfExists('audit_sessions');
    }
};
