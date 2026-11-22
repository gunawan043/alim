<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan rapor per predikat dalam bahasa Arab. Dipakai saat mencetak rapor
 * berbahasa Arab; teks Indonesia tetap di kolom `note`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rapor_predicate_notes')) {
            return;
        }

        Schema::table('rapor_predicate_notes', function (Blueprint $table) {
            if (! Schema::hasColumn('rapor_predicate_notes', 'note_ar')) {
                $table->text('note_ar')->nullable()->after('note');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('rapor_predicate_notes')
            && Schema::hasColumn('rapor_predicate_notes', 'note_ar')) {
            Schema::table('rapor_predicate_notes', function (Blueprint $table) {
                $table->dropColumn('note_ar');
            });
        }
    }
};
