<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dokumen_iso', function (Blueprint $table) {
            $table->string('kategori', 20)->nullable()->default('PROSEDUR')->after('keterangan');
        });
    }

    public function down(): void
    {
        Schema::table('dokumen_iso', function (Blueprint $table) {
            $table->dropColumn('kategori');
        });
    }
};
