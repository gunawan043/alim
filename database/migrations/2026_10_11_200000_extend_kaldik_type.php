<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tipe Kaldik "program_bersama" + kolom type jadi string
 * (agar tipe baru tidak perlu alter enum lagi).
 */
return new class extends Migration
{
    public function up(): void
    {
        // MySQL: enum → varchar; SQLite: rebuild kolom (didukung Laravel).
        try {
            Schema::table('kaldik', function (Blueprint $table) {
                $table->string('type', 30)->nullable()->change();
            });
        } catch (\Throwable) {
            // Biarkan: kolom sudah string / driver tidak mendukung change().
        }
    }

    public function down(): void
    {
        // Tidak dikembalikan ke enum (data historis tetap valid).
    }
};
