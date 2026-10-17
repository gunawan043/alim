<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notifikasi universal per SATUAN KERJA (work unit):
 *  - work_unit_id null  = notifikasi global (semua satuan)
 *  - work_unit_id terisi = hanya untuk pengguna pada satuan kerja tersebut
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('notifications_universal', 'work_unit_id')) {
            Schema::table('notifications_universal', function (Blueprint $table) {
                $table->uuid('work_unit_id')->nullable()->after('user_id');
                $table->index(['user_id', 'work_unit_id'], 'notif_user_workunit_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('notifications_universal', 'work_unit_id')) {
            Schema::table('notifications_universal', function (Blueprint $table) {
                $table->dropIndex('notif_user_workunit_idx');
                $table->dropColumn('work_unit_id');
            });
        }
    }
};
