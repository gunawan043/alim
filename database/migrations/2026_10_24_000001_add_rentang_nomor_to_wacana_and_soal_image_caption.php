<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('wacana_soal', 'rentang_nomor')) {
            Schema::table('wacana_soal', function (Blueprint $table) {
                $table->string('rentang_nomor', 100)->nullable()->after('sumber');
            });
        }

        if (! Schema::hasColumn('soal', 'gambar_keterangan')) {
            Schema::table('soal', function (Blueprint $table) {
                $table->string('gambar_keterangan', 255)->nullable()->after('gambar_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('soal', 'gambar_keterangan')) {
            Schema::table('soal', function (Blueprint $table) { $table->dropColumn('gambar_keterangan'); });
        }
        if (Schema::hasColumn('wacana_soal', 'rentang_nomor')) {
            Schema::table('wacana_soal', function (Blueprint $table) { $table->dropColumn('rentang_nomor'); });
        }
    }
};
