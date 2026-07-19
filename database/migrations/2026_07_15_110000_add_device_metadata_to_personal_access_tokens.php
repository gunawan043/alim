<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 2 (ADR-018) — additive enrichment of personal_access_tokens.
 *
 * Adds the columns required by the session-management API surface
 * (GET /auth/sessions, PATCH /auth/sessions/current, DELETE
 * /auth/sessions/others) WITHOUT modifying the original migration.
 *
 * Every column is nullable so legacy rows continue to load.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('personal_access_tokens')) {
            return;
        }

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            if (! Schema::hasColumn('personal_access_tokens', 'device_label')) {
                $table->string('device_label', 80)->nullable()->after('name');
            }

            if (! Schema::hasColumn('personal_access_tokens', 'ip_last')) {
                $table->string('ip_last', 45)->nullable()->after('device_label');
            }

            if (! Schema::hasColumn('personal_access_tokens', 'fingerprint')) {
                $table->string('fingerprint', 32)->nullable()->after('ip_last');
            }
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->index('fingerprint', 'pat_fingerprint_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('personal_access_tokens')) {
            return;
        }

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropIndex('pat_fingerprint_idx');

            if (Schema::hasColumn('personal_access_tokens', 'fingerprint')) {
                $table->dropColumn('fingerprint');
            }

            if (Schema::hasColumn('personal_access_tokens', 'ip_last')) {
                $table->dropColumn('ip_last');
            }

            if (Schema::hasColumn('personal_access_tokens', 'device_label')) {
                $table->dropColumn('device_label');
            }
        });
    }
};
