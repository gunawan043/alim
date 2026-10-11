<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tahap 3 — Kehadiran terhubung (asrama/UKS/mapel):
 *  sumber otomatis + jejak verifikasi "saling cek kesesuaian".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('admin_presensi_harian', 'source')) {
            Schema::table('admin_presensi_harian', function (Blueprint $table) {
                $table->string('source', 30)->nullable()->after('status')
                    ->comment('manual|uks|boarding_health|boarding_leave|boarding_discharged');
                $table->uuid('source_ref')->nullable()->after('source');
                $table->boolean('auto_filled')->default(false)->after('source_ref');
                $table->timestamp('verified_at')->nullable()->after('auto_filled');
                $table->uuid('verified_by')->nullable()->after('verified_at');
            });
        }

        if (! Schema::hasColumn('admin_presensi_siswa', 'source')) {
            Schema::table('admin_presensi_siswa', function (Blueprint $table) {
                $table->string('source', 30)->nullable()->after('status');
                $table->uuid('source_ref')->nullable()->after('source');
                $table->boolean('auto_filled')->default(false)->after('source_ref');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('admin_presensi_harian', 'source')) {
            Schema::table('admin_presensi_harian', function (Blueprint $table) {
                $table->dropColumn(['source', 'source_ref', 'auto_filled', 'verified_at', 'verified_by']);
            });
        }

        if (Schema::hasColumn('admin_presensi_siswa', 'source')) {
            Schema::table('admin_presensi_siswa', function (Blueprint $table) {
                $table->dropColumn(['source', 'source_ref', 'auto_filled']);
            });
        }
    }
};
