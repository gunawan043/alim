<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Manual schedule editor boleh menyimpan bentrok sebagai indikator visual.
 * Generator tetap berusaha menghindari bentrok.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('jadwal_kbms')) {
            return;
        }

        try {
            Schema::table('jadwal_kbms', function (Blueprint $table) {
                $table->dropUnique('uk_jadwal_guru_hari_sl');
            });
        } catch (Throwable) {
            // Index dapat berbeda pada database lama.
        }

        foreach ([
            'trg_jadwal_guru_check_ins',
            'trg_jadwal_guru_check_upd',
            'trg_jadwal_rsm_check_ins',
            'trg_jadwal_rsm_check_upd',
        ] as $trigger) {
            try {
                DB::unprepared("DROP TRIGGER IF EXISTS {$trigger}");
            } catch (Throwable) {
                // SQLite / database tanpa trigger.
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('jadwal_kbms')) {
            return;
        }

        try {
            Schema::table('jadwal_kbms', function (Blueprint $table) {
                $table->unique(['teacher_id', 'day_of_week', 'slot_index'], 'uk_jadwal_guru_hari_sl');
            });
        } catch (Throwable) {
            // Abaikan bila sudah ada.
        }
    }
};
