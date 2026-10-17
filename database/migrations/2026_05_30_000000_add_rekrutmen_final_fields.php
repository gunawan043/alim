<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_applications', function (Blueprint $table) {
            // Nilai tes terpisah (tes tulis, tes praktikum, wawancara)
            $table->decimal('nilai_tes_tulis', 5, 2)->nullable()->after('nilai_tes');
            $table->decimal('nilai_tes_praktikum', 5, 2)->nullable()->after('nilai_tes_tulis');

            // Status akhir - hasil keputusan akhir rekrutmen
            $table->enum('status_akhir', ['diterima', 'ditolak', 'cadangan', null])->nullable()->after('ranking');

            // Hari tes / test day
            $table->date('tanggal_tes')->nullable()->after('diproses_at');
            $table->string('lokasi_tes')->nullable()->after('tanggal_tes');

            // Keterangan keputusan
            $table->text('catatan_keputusan')->nullable()->after('status_akhir');
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_applications', function (Blueprint $table) {
            $table->dropColumn([
                'nilai_tes_tulis',
                'nilai_tes_praktikum',
                'status_akhir',
                'tanggal_tes',
                'lokasi_tes',
                'catatan_keputusan',
            ]);
        });
    }
};
