<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('wacana_soal', 'posisi_setelah_soal')) {
            Schema::table('wacana_soal', function (Blueprint $table) {
                $table->unsignedInteger('posisi_setelah_soal')->default(0)->after('rentang_nomor');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('wacana_soal', 'posisi_setelah_soal')) {
            Schema::table('wacana_soal', function (Blueprint $table) {
                $table->dropColumn('posisi_setelah_soal');
            });
        }
    }
};
