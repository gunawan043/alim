<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengingat kembali ke asrama tidak lagi memakai konsep H-1.
 *
 * Pengingat kini hanya berjalan setelah `expected_return_datetime` terlewati
 * dan diulang sesuai `interval_minutes` per asrama, sehingga dua kolom
 * pengaturan H-1 tidak lagi dipakai.
 */
return new class extends Migration
{
    private const COLUMNS = ['h1_reminder_enabled', 'h1_reminder_time'];

    public function up(): void
    {
        $drop = array_values(array_filter(
            self::COLUMNS,
            fn (string $column) => Schema::hasColumn('dormitory_notification_settings', $column),
        ));

        if ($drop === []) {
            return;
        }

        Schema::table('dormitory_notification_settings', function (Blueprint $table) use ($drop) {
            $table->dropColumn($drop);
        });
    }

    public function down(): void
    {
        Schema::table('dormitory_notification_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('dormitory_notification_settings', 'h1_reminder_enabled')) {
                $table->boolean('h1_reminder_enabled')->default(true);
            }

            if (! Schema::hasColumn('dormitory_notification_settings', 'h1_reminder_time')) {
                $table->string('h1_reminder_time', 5)->default('16:00');
            }
        });
    }
};
