<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('paket_soal', 'layout_settings')) {
            Schema::table('paket_soal', function (Blueprint $table) {
                $table->json('layout_settings')->nullable()->after('kkm');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('paket_soal', 'layout_settings')) {
            Schema::table('paket_soal', function (Blueprint $table) {
                $table->dropColumn('layout_settings');
            });
        }
    }
};
