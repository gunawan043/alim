<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RSA boleh diisi manual per santri. Bila manual, nilai otomatis
 * (STS + SAS) / 2 tidak menimpa isian guru; NR Murni tetap memakai RSA tersebut.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_nilai_sumatif') || Schema::hasColumn('admin_nilai_sumatif', 'rsa_manual')) {
            return;
        }

        Schema::table('admin_nilai_sumatif', function (Blueprint $table) {
            $table->boolean('rsa_manual')->default(false);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_nilai_sumatif') || ! Schema::hasColumn('admin_nilai_sumatif', 'rsa_manual')) {
            return;
        }

        Schema::table('admin_nilai_sumatif', function (Blueprint $table) {
            $table->dropColumn('rsa_manual');
        });
    }
};
