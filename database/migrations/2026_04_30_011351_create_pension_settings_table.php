<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pension_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed default values
        DB::table('pension_settings')->insert([
            ['setting_key' => 'bup_age',          'setting_value' => '58',  'description' => 'Umur Batas Usia Pensiun (tahun)'],
            ['setting_key' => 'notification_months', 'setting_value' => '6', 'description' => 'Jumlah bulan sebelum BUP untuk kirim notifikasi'],
            ['setting_key' => 'early_retirement_age', 'setting_value' => '55', 'description' => 'Umur minimum untuk early retirement'],
            ['setting_key' => 'min_service_years', 'setting_value' => '10', 'description' => 'Minimum tahun masa kerja untuk eligible pensions'],
            ['setting_key' => 'pension_percentage', 'setting_value' => '75', 'description' => 'Persentase dana pensions dari gaji terakhir (%)'],
            ['setting_key' => 'early_retirement_years', 'setting_value' => '2', 'description' => 'Berapa tahun sebelum BUP bisa ajukan early retirement'],
            ['setting_key' => 'notification_enabled', 'setting_value' => '1', 'description' => 'Aktifkan notifikasi pensiun: 1=yes, 0=no'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pension_settings');
    }
};
