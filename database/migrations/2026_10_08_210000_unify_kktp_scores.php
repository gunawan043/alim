<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Satukan nilai KKTP: `kkm_score` menjadi nilai otoritatif (dipakai Leger & Rapor),
     * `kktp_score` dipertahankan sebagai mirror untuk kompatibilitas pembaca lama.
     *
     * Backfill dilakukan dua arah lalu disamakan — tidak ada kolom yang dihapus.
     */
    public function up(): void
    {
        // Isi yang kosong dari nilai yang ada.
        DB::table('subject_kktp')
            ->whereNull('kkm_score')
            ->whereNotNull('kktp_score')
            ->update(['kkm_score' => DB::raw('kktp_score')]);

        DB::table('subject_kktp')
            ->whereNull('kktp_score')
            ->whereNotNull('kkm_score')
            ->update(['kktp_score' => DB::raw('kkm_score')]);

        // Bila keduanya terisi tapi berbeda → samakan ke kkm_score (otoritatif).
        DB::table('subject_kktp')
            ->whereNotNull('kkm_score')
            ->whereNotNull('kktp_score')
            ->whereColumn('kkm_score', '!=', 'kktp_score')
            ->update(['kktp_score' => DB::raw('kkm_score')]);
    }

    public function down(): void
    {
        // Tidak ada perubahan struktural — tidak ada yang dibalik.
    }
};
