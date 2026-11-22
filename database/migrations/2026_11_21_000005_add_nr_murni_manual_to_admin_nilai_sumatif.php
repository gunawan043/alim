<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NR Murni boleh diisi manual per santri. Bila manual, nilai otomatis
 * (RS + RSA) / 2 tidak menimpa isian guru.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_nilai_sumatif') || Schema::hasColumn('admin_nilai_sumatif', 'nr_murni_manual')) {
            return;
        }

        Schema::table('admin_nilai_sumatif', function (Blueprint $table) {
            $table->boolean('nr_murni_manual')->default(false);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_nilai_sumatif') || ! Schema::hasColumn('admin_nilai_sumatif', 'nr_murni_manual')) {
            return;
        }

        Schema::table('admin_nilai_sumatif', function (Blueprint $table) {
            $table->dropColumn('nr_murni_manual');
        });
    }
};
