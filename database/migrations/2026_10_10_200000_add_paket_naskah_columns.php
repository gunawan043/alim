<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tahap 2 — Paket Soal sebagai NASKAH:
 *  - Komposisi eksplisit (jenis/jumlah/bobot/urutan) pada paket.
 *  - Snapshot butir saat paket final (pertanyaan, opsi, kunci, pembahasan, bobot).
 *  - Nomor & jenis per butir (dukung pg/mcc/bs/jodoh/isian/uraian).
 *  - Distribusi dengan fase diterima (received).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('paket_soal', 'komposisi')) {
            Schema::table('paket_soal', function (Blueprint $table) {
                $table->json('komposisi')->nullable()->after('instruksi_umum')
                    ->comment('Naskah: daftar bagian {urutan, jenis, jumlah, bobot}');
                $table->timestamp('snapshot_at')->nullable()->after('komposisi')
                    ->comment('Waktu butir dibekukan menjadi naskah final');
            });
        }

        if (! Schema::hasColumn('paket_soal_items', 'konten_snapshot')) {
            Schema::table('paket_soal_items', function (Blueprint $table) {
                $table->json('konten_snapshot')->nullable()->after('bobot_override')
                    ->comment('Salinan butir saat final: pertanyaan, opsi, kunci, pembahasan, bobot');
                $table->string('jenis_soal', 20)->nullable()->after('konten_snapshot');
                $table->unsignedInteger('nomor')->nullable()->after('jenis_soal');
            });
        }

        if (! Schema::hasColumn('paket_soal_distributions', 'received_at')) {
            Schema::table('paket_soal_distributions', function (Blueprint $table) {
                $table->timestamp('received_at')->nullable()->after('note');
                $table->uuid('received_by')->nullable()->after('received_at');
            });
        }

        // Backfill: nomor mengikuti urutan; jenis dari tipe soal.
        if (Schema::hasColumn('paket_soal_items', 'nomor')) {
            DB::table('paket_soal_items')->whereNull('nomor')->update(['nomor' => DB::raw('urutan')]);

            DB::table('paket_soal_items')
                ->whereNull('jenis_soal')
                ->orderBy('id')
                ->chunk(200, function ($items) {
                    foreach ($items as $item) {
                        $tipe = DB::table('soal')->where('id', $item->soal_id)->value('tipe_soal');
                        if ($tipe) {
                            DB::table('paket_soal_items')->where('id', $item->id)->update(['jenis_soal' => $tipe]);
                        }
                    }
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('paket_soal_items', 'konten_snapshot')) {
            Schema::table('paket_soal_items', function (Blueprint $table) {
                $table->dropColumn(['konten_snapshot', 'jenis_soal', 'nomor']);
            });
        }

        if (Schema::hasColumn('paket_soal', 'komposisi')) {
            Schema::table('paket_soal', function (Blueprint $table) {
                $table->dropColumn(['komposisi', 'snapshot_at']);
            });
        }

        if (Schema::hasColumn('paket_soal_distributions', 'received_at')) {
            Schema::table('paket_soal_distributions', function (Blueprint $table) {
                $table->dropColumn(['received_at', 'received_by']);
            });
        }
    }
};
