<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_nilai_formatif', function (Blueprint $table) {
            $table->decimal('nr_final', 5, 2)->nullable()
                ->comment('Nilai Formatif Final = rata-rata skor, auto (readonly display)')
                ->after('skor_antarteman');
            $table->string('ket', 255)->nullable()
                ->comment('Keterangan / Catatan')
                ->after('nr_final');
        });
    }

    public function down(): void
    {
        Schema::table('admin_nilai_formatif', function (Blueprint $table) {
            $table->dropColumn(['nr_final', 'ket']);
        });
    }
};
