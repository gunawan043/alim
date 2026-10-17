<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_nilai_sumatif', function (Blueprint $table) {
            $table->decimal('raport_sts', 5, 2)->nullable()
                ->comment('Nilai STS versi raport (editable, override STS saat hitung NR Final)')
                ->after('sts');
        });
    }

    public function down(): void
    {
        Schema::table('admin_nilai_sumatif', function (Blueprint $table) {
            $table->dropColumn('raport_sts');
        });
    }
};
