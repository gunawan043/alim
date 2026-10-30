<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_health_metrics') || ! Schema::hasColumn('student_health_metrics', 'measurement_session')) {
            return;
        }

        Schema::table('student_health_metrics', function (Blueprint $table) {
            // Form UKS memakai awal_tahun/tengah_tahun/akhir_tahun/lainnya.
            // Ubah enum lama menjadi varchar agar semua pilihan UI dapat disimpan.
            $table->string('measurement_session', 50)->default('bulanan')->change();
        });
    }

    public function down(): void
    {
        // Tidak dikembalikan ke enum lama karena dapat memotong data yang sudah ada.
    }
};
