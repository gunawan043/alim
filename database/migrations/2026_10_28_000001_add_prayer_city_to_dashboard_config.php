<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('system_settings')->where('key', 'dashboard_prayer_city')->exists()) {
            return;
        }

        DB::table('system_settings')->insert([
            'key' => 'dashboard_prayer_city',
            'value' => null,
            'type' => 'string',
            'description' => 'Kota default jadwal shalat dashboard bila unit belum memiliki kota.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('system_settings')->where('key', 'dashboard_prayer_city')->delete();
    }
};
