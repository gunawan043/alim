<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * V8 — Review & Approval nama Arab.
 *
 * Status:
 *  - NULL      : belum ada nama Arab.
 *  - pending   : diisi lewat import/generator, belum ditinjau.
 *  - approved  : disetujui (dipakai pada dokumen resmi seperti rapor).
 *
 * Nama yang sudah ada sebelum fitur review dianggap approved (data lama
 * yang diisi manual), bukan historesis palsu.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['students', 'users'] as $table) {
            if (! Schema::hasColumn($table, 'name_ar_status')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->string('name_ar_status', 10)->nullable()->after('name_ar');
                    $table->timestamp('name_ar_reviewed_at')->nullable()->after('name_ar_status');
                    $table->uuid('name_ar_reviewed_by')->nullable()->after('name_ar_reviewed_at');
                });
            }
        }

        // Data lama yang sudah memiliki nama Arab dianggap sudah ditinjau.
        foreach (['students', 'users'] as $table) {
            if (Schema::hasColumn($table, 'name_ar_status')) {
                DB::table($table)
                    ->whereNotNull('name_ar')
                    ->where('name_ar', '!=', '')
                    ->whereNull('name_ar_status')
                    ->update([
                        'name_ar_status' => 'approved',
                        'name_ar_reviewed_at' => now(),
                    ]);
            }
        }
    }

    public function down(): void
    {
        foreach (['students', 'users'] as $table) {
            if (Schema::hasColumn($table, 'name_ar_status')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn(['name_ar_status', 'name_ar_reviewed_at', 'name_ar_reviewed_by']);
                });
            }
        }
    }
};
