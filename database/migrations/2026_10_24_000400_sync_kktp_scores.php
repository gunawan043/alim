<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Pastikan KKTP lama yang hanya memiliki salah satu kolom tetap terbaca.
     *
     * `kkm_score` adalah nilai otoritatif untuk leger/rapor, sedangkan
     * `kktp_score` adalah mirror untuk layar Peta Kurikulum. Data yang dibuat
     * lewat input leger lama dapat mengisi `kkm_score` saja; migrasi ini
     * menyamakan keduanya agar N-KKTP tidak tampil kosong.
     */
    public function up(): void
    {
        DB::table('subject_kktp')
            ->whereNull('kkm_score')
            ->whereNotNull('kktp_score')
            ->update(['kkm_score' => DB::raw('kktp_score')]);

        DB::table('subject_kktp')
            ->whereNull('kktp_score')
            ->whereNotNull('kkm_score')
            ->update(['kktp_score' => DB::raw('kkm_score')]);

        DB::table('subject_kktp')
            ->whereNotNull('kkm_score')
            ->whereNotNull('kktp_score')
            ->whereColumn('kkm_score', '!=', 'kktp_score')
            ->update(['kktp_score' => DB::raw('kkm_score')]);
    }

    public function down(): void
    {
        // Tidak ada perubahan struktur; nilai tidak dikembalikan.
    }
};
