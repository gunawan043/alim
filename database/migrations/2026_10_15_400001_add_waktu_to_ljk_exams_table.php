<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jam pelaksanaan ujian LJK — dipakai pada kop lembar soal
 * (mis. "07.30 – 09.00"). Aditif & nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ljk_exams')) {
            return;
        }

        if (! Schema::hasColumn('ljk_exams', 'waktu_mulai')) {
            Schema::table('ljk_exams', function (Blueprint $table) {
                $table->time('waktu_mulai')->nullable()->after('tanggal');
                $table->time('waktu_selesai')->nullable()->after('waktu_mulai');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ljk_exams') && Schema::hasColumn('ljk_exams', 'waktu_mulai')) {
            Schema::table('ljk_exams', function (Blueprint $table) {
                $table->dropColumn(['waktu_mulai', 'waktu_selesai']);
            });
        }
    }
};
