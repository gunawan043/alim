<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tahfidz_tasmian_participants')) {
            return;
        }

        Schema::table('tahfidz_tasmian_participants', function (Blueprint $table) {
            if (! Schema::hasColumn('tahfidz_tasmian_participants', 'requested_by')) {
                $table->uuid('requested_by')->nullable()->after('mustami_id');
            }
            if (! Schema::hasColumn('tahfidz_tasmian_participants', 'requested_at')) {
                $table->timestamp('requested_at')->nullable()->after('requested_by');
            }
            if (! Schema::hasColumn('tahfidz_tasmian_participants', 'verification_status')) {
                $table->string('verification_status', 20)->default('pending')->after('status');
            }
            if (! Schema::hasColumn('tahfidz_tasmian_participants', 'verified_by')) {
                $table->uuid('verified_by')->nullable()->after('verification_status');
            }
            if (! Schema::hasColumn('tahfidz_tasmian_participants', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified_by');
            }
            if (! Schema::hasColumn('tahfidz_tasmian_participants', 'verification_notes')) {
                $table->text('verification_notes')->nullable()->after('verified_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tahfidz_tasmian_participants')) {
            return;
        }

        Schema::table('tahfidz_tasmian_participants', function (Blueprint $table) {
            $table->dropColumn([
                'requested_by', 'requested_at', 'verification_status',
                'verified_by', 'verified_at', 'verification_notes',
            ]);
        });
    }
};
