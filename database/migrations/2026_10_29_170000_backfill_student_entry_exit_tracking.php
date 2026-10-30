<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill jejak masuk/keluar untuk data santri yang sudah ada.
 *
 * - entry_source : 'mutation' bila punya surat mutasi masuk, sisanya 'manual'
 *   (import Excel dan registrasi wali lama tidak dapat dibedakan dari data).
 * - exit_reason  : diturunkan dari status santri.
 * - exit_date    : tanggal kelulusan bila ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        $mutationStudentIds = DB::table('student_mutations_in')
            ->whereNotNull('student_id')
            ->select('student_id');

        DB::table('students')
            ->whereIn('id', $mutationStudentIds)
            ->whereNull('entry_source')
            ->update(['entry_source' => 'mutation']);

        DB::table('students')
            ->whereNull('entry_source')
            ->update(['entry_source' => 'manual']);

        DB::table('students')->whereNull('exit_reason')->where('status', 'graduate')
            ->update(['exit_reason' => 'graduation']);
        DB::table('students')->whereNull('exit_reason')->where('status', 'transfer_out')
            ->update(['exit_reason' => 'mutation']);
        DB::table('students')->whereNull('exit_reason')->where('status', 'dropped')
            ->update(['exit_reason' => 'dropout']);

        DB::table('students')
            ->whereNotNull('exit_reason')
            ->whereNull('exit_date')
            ->whereNotNull('graduation_date')
            ->update(['exit_date' => DB::raw('graduation_date')]);
    }

    /**
     * Backfill tidak dibalik: menghapus jejak yang sudah tercatat akan
     * menghilangkan informasi asal-usul data yang sah.
     */
    public function down(): void
    {
        //
    }
};
