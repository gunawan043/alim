<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opsi mencetak foto santri pada Surat Keterangan Lulus.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('graduation_certificate_settings', 'show_photo')) {
            Schema::table('graduation_certificate_settings', function (Blueprint $table) {
                $table->boolean('show_photo')->default(true)->after('show_ijazah');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('graduation_certificate_settings', 'show_photo')) {
            Schema::table('graduation_certificate_settings', function (Blueprint $table) {
                $table->dropColumn('show_photo');
            });
        }
    }
};
