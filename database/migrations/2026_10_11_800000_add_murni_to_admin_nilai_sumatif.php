<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nilai Murni STS/SAS = nilai hasil ujian TANPA ditambah elemen penilaian lain.
 * Kolom STS/SAS (nilai komponen) hanya boleh diisi setelah Nilai Murni terisi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('admin_nilai_sumatif', 'sts_murni')) {
            Schema::table('admin_nilai_sumatif', function (Blueprint $table) {
                $table->decimal('sts_murni', 5, 2)->nullable()->after('sts')->comment('Nilai Murni STS (hasil ujian, tanpa elemen lain)');
                $table->decimal('sas_murni', 5, 2)->nullable()->after('sas')->comment('Nilai Murni SAS (hasil ujian, tanpa elemen lain)');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('admin_nilai_sumatif', 'sts_murni')) {
            Schema::table('admin_nilai_sumatif', function (Blueprint $table) {
                $table->dropColumn(['sts_murni', 'sas_murni']);
            });
        }
    }
};
