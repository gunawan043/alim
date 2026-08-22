<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_attendance_recaps', function (Blueprint $table) {
            $table->integer('total_selesai_mengajar')->default(0)
                ->after('total_jam_hadir')
                ->comment('Jumlah slot yang dijadwalkan & selesai diajar dengan scan masuk+keluar');
            $table->integer('total_keluar_cepat')->default(0)
                ->comment('Guru pulang lebih cepat dari jadwal (early leave)');
            $table->integer('total_tidak_keluar')->default(0)
                ->comment('Guru tidak melakukan scan keluar (lupa atau di-auto-mark oleh cron)');
            $table->integer('total_durasi_menit')->default(0)
                ->comment('Total durasi mengajar aktual (SUM duration_minutes)');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_attendance_recaps', function (Blueprint $table) {
            $table->dropColumn([
                'total_selesai_mengajar',
                'total_keluar_cepat',
                'total_tidak_keluar',
                'total_durasi_menit',
            ]);
        });
    }
};
