<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Melengkapi format Kisi-Kisi Soal: kolom Materi, Bentuk Soal, dan Nomor Soal
 * pada tiap item kisi-kisi (merujuk nomor pada paket soal terkait).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kisi_kisi_soal_items')) {
            return;
        }

        Schema::table('kisi_kisi_soal_items', function (Blueprint $table) {
            if (! Schema::hasColumn('kisi_kisi_soal_items', 'materi')) {
                $table->string('materi', 255)->nullable()->after('tp_id');
            }
            if (! Schema::hasColumn('kisi_kisi_soal_items', 'bentuk_soal')) {
                $table->string('bentuk_soal', 20)->nullable()->after('materi');
            }
            if (! Schema::hasColumn('kisi_kisi_soal_items', 'nomor_soal')) {
                $table->string('nomor_soal', 40)->nullable()->after('bentuk_soal');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('kisi_kisi_soal_items')) {
            return;
        }

        Schema::table('kisi_kisi_soal_items', function (Blueprint $table) {
            foreach (['materi', 'bentuk_soal', 'nomor_soal'] as $column) {
                if (Schema::hasColumn('kisi_kisi_soal_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
