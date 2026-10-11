<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tahap 2 — RPM sebagai dokumen berversi:
 *  - Satu dokumen CURRENT per konteks akademik (mapel + jenjang + TA + semester).
 *  - Versi: root_id/parent_id/version/is_current — revisi = versi baru.
 *  - Workflow terpisah: draft → review → (revisi) → approved.
 *  - Final dikunci: locked_at/locked_by diisi saat approved/final.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('perangkat_pembelajaran', 'workflow_status')) {
            Schema::table('perangkat_pembelajaran', function (Blueprint $table) {
                $table->string('workflow_status', 20)->default('draft')->after('status')
                    ->comment('draft|review|revisi|approved — terpisah dari status final (kunci)');
            });
        }

        if (! Schema::hasColumn('perangkat_pembelajaran', 'root_id')) {
            Schema::table('perangkat_pembelajaran', function (Blueprint $table) {
                $table->uuid('root_id')->nullable()->after('workflow_status');
                $table->uuid('parent_id')->nullable()->after('root_id');
                $table->unsignedInteger('version')->default(1)->after('parent_id');
                $table->boolean('is_current')->default(true)->after('version');
                $table->timestamp('submitted_at')->nullable()->after('is_current');
                $table->timestamp('approved_at')->nullable()->after('submitted_at');
                $table->uuid('approved_by')->nullable()->after('approved_at');
                $table->timestamp('locked_at')->nullable()->after('approved_by');
                $table->uuid('locked_by')->nullable()->after('locked_at');

                $table->index(['root_id', 'version'], 'perangkat_root_version_idx');
                $table->index(
                    ['school_id', 'academic_year_id', 'semester', 'subject_id', 'grade_level_id', 'is_current'],
                    'perangkat_context_current_idx'
                );
            });
        }

        // Backfill dokumen lama: root = dirinya sendiri, current = true.
        if (Schema::hasColumn('perangkat_pembelajaran', 'root_id')) {
            DB::table('perangkat_pembelajaran')
                ->whereNull('root_id')
                ->update(['root_id' => DB::raw('id')]);

            // Dokumen yang sudah final dianggap approved + terkunci.
            DB::table('perangkat_pembelajaran')
                ->where('status', 'final')
                ->whereNull('locked_at')
                ->update([
                    'workflow_status' => 'approved',
                    'locked_at' => DB::raw('updated_at'),
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('perangkat_pembelajaran', 'root_id')) {
            Schema::table('perangkat_pembelajaran', function (Blueprint $table) {
                $table->dropIndex('perangkat_root_version_idx');
                $table->dropIndex('perangkat_context_current_idx');
                $table->dropColumn([
                    'workflow_status', 'root_id', 'parent_id', 'version', 'is_current',
                    'submitted_at', 'approved_at', 'approved_by', 'locked_at', 'locked_by',
                ]);
            });
        }
    }
};
