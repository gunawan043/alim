<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nama pelanggaran diambil dari katalog yang lebarnya 191 karakter,
     * sementara kolomnya hanya 100 sehingga validasi menolak nama panjang.
     */
    public function up(): void
    {
        // SQLite tidak menegakkan panjang VARCHAR, jadi tidak perlu
        // membangun ulang tabel. MySQL perlu kolom yang benar-benar lebar.
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('dormitory_violations', function (Blueprint $table) {
            $table->string('violation_type', 191)->change();
        });

        Schema::table('violation_points', function (Blueprint $table) {
            $table->string('violation_type', 191)->change();
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('dormitory_violations', function (Blueprint $table) {
            $table->string('violation_type', 100)->change();
        });

        Schema::table('violation_points', function (Blueprint $table) {
            $table->string('violation_type', 100)->change();
        });
    }
};
