<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Penyempurnaan similarity Bank Soal:
 *  1) content_hash TIDAK boleh unik — exact duplicate adalah indikator untuk
 *     ditinjau (bukan alasan blokir). Sebelumnya unique index membuat input
 *     soal identik gagal simpan alih-alih memberi peringatan.
 *  2) Kolom pencatatan keputusan "tetap dilanjutkan meskipun ada kemiripan"
 *     beserta alasannya (dapat dilihat penyusun & reviewer).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Lepas unique index content_hash (jika ada)
        try {
            Schema::table('soal', function (Blueprint $table) {
                $table->dropUnique('soal_content_hash_unique');
            });
        } catch (\Throwable) {
            try {
                DB::statement('DROP INDEX IF EXISTS soal_content_hash_unique');
            } catch (\Throwable) {
                // Index memang tidak ada — aman diabaikan.
            }
        }

        // ── Catatan pengecualian similarity
        if (! Schema::hasColumn('soal', 'similarity_ack_note')) {
            Schema::table('soal', function (Blueprint $table) {
                $table->text('similarity_ack_note')->nullable()->after('similarity_summary');
                $table->uuid('similarity_ack_by')->nullable()->after('similarity_ack_note');
                $table->timestamp('similarity_ack_at')->nullable()->after('similarity_ack_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('soal', 'similarity_ack_note')) {
            Schema::table('soal', function (Blueprint $table) {
                $table->dropColumn(['similarity_ack_note', 'similarity_ack_by', 'similarity_ack_at']);
            });
        }

        // Tidak mengembalikan unique index (data duplikat historis bisa ada).
    }
};
