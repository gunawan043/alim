<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tandai bank penyimpan otomatis milik Soal Saya.
 *
 * Sebelumnya QuickSoalService membuat bank "Soal Saya — {mapel}" dengan
 * deskripsi "Bank soal pribadi yang dibuat otomatis dari Soal Saya.",
 * sehingga soal pribadi muncul ganda: di Soal Saya dan di menu Bank Soal.
 *
 * Penanda ditulis di awal deskripsi (prefix) agar dapat difilter dengan
 * `LIKE` — portabel di MySQL maupun SQLite.
 */
return new class extends Migration
{
    private const MARKER = '[soal-saya]';

    private const OLD_DESC = 'Bank soal pribadi yang dibuat otomatis dari Soal Saya.';

    private const NEW_DESC = '[soal-saya] Bank pribadi Soal Saya. Soal di sini dikelola di menu Soal Saya, bukan sebagai koleksi bersama.';

    public function up(): void
    {
        if (! Schema::hasTable('bank_soal') || ! Schema::hasColumn('bank_soal', 'deskripsi')) {
            return;
        }

        DB::table('bank_soal')
            ->where('deskripsi', self::OLD_DESC)
            ->update(['deskripsi' => self::NEW_DESC]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('bank_soal') || ! Schema::hasColumn('bank_soal', 'deskripsi')) {
            return;
        }

        DB::table('bank_soal')
            ->where('deskripsi', self::NEW_DESC)
            ->update(['deskripsi' => self::OLD_DESC]);
    }
};
