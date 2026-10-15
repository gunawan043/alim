<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sub-kolom Penilaian Asesmen Formatif (default 4 kolom per aspek).
     * Kolom scalar lama (skor_*) tetap diisi rata-rata per aspek agar tampilan lama tetap jalan.
     */
    public function up(): void
    {
        if (! Schema::hasTable('admin_nilai_formatif')) {
            return;
        }

        Schema::table('admin_nilai_formatif', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_nilai_formatif', 'lkpd_scores')) {
                $table->json('lkpd_scores')->nullable();
            }
            if (! Schema::hasColumn('admin_nilai_formatif', 'diskusi_scores')) {
                $table->json('diskusi_scores')->nullable();
            }
            if (! Schema::hasColumn('admin_nilai_formatif', 'kuis_scores')) {
                $table->json('kuis_scores')->nullable();
            }
            if (! Schema::hasColumn('admin_nilai_formatif', 'antarteman_scores')) {
                $table->json('antarteman_scores')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_nilai_formatif')) {
            return;
        }

        Schema::table('admin_nilai_formatif', function (Blueprint $table) {
            $table->dropColumn(['lkpd_scores', 'diskusi_scores', 'kuis_scores', 'antarteman_scores']);
        });
    }
};
