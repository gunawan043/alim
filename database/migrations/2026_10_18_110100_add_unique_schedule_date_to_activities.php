<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dormitory_activities') && Schema::hasColumn('dormitory_activities', 'schedule_id')) {
            try {
                Schema::table('dormitory_activities', function (Blueprint $table) {
                    $table->unique(['schedule_id', 'activity_date'], 'unique_schedule_activity_date');
                });
            } catch (\Throwable) {
                // Index sudah ada (mis. saat migrate ulang).
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dormitory_activities')) {
            try {
                Schema::table('dormitory_activities', fn (Blueprint $table) => $table->dropUnique('unique_schedule_activity_date'));
            } catch (\Throwable) {
            }
        }
    }
};
