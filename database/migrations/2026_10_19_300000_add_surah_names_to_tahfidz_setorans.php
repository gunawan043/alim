<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tahfidz_setorans', 'surah_start_name')) {
            Schema::table('tahfidz_setorans', function (Blueprint $table) {
                $table->string('surah_start_name', 100)->nullable()->after('surah_start_id');
                $table->string('surah_end_name', 100)->nullable()->after('surah_end_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tahfidz_setorans', 'surah_start_name')) {
            Schema::table('tahfidz_setorans', function (Blueprint $table) {
                $table->dropColumn(['surah_start_name', 'surah_end_name']);
            });
        }
    }
};
