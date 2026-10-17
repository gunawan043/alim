<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V11 — Integrasi draft AI ke Bank Soal / RPM.
 * Menyimpan referensi hasil simpan + bahasa draft.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ai_generated_contents', 'bahasa')) {
            Schema::table('ai_generated_contents', function (Blueprint $table) {
                $table->string('bahasa', 10)->nullable()->after('semester');
            });
        }

        if (! Schema::hasColumn('ai_generated_contents', 'saved_bank_id')) {
            Schema::table('ai_generated_contents', function (Blueprint $table) {
                $table->uuid('saved_bank_id')->nullable()->after('approved_at');
                $table->uuid('saved_perangkat_id')->nullable()->after('saved_bank_id');
                $table->timestamp('saved_at')->nullable()->after('saved_perangkat_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ai_generated_contents', 'saved_bank_id')) {
            Schema::table('ai_generated_contents', function (Blueprint $table) {
                $table->dropColumn(['bahasa', 'saved_bank_id', 'saved_perangkat_id', 'saved_at']);
            });
        }
    }
};
