<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rapor_distribution_settings')) {
            return;
        }

        Schema::table('rapor_distribution_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('rapor_distribution_settings', 'show_qr')) {
                $table->boolean('show_qr')->default(false);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('rapor_distribution_settings')) {
            return;
        }

        Schema::table('rapor_distribution_settings', function (Blueprint $table) {
            if (Schema::hasColumn('rapor_distribution_settings', 'show_qr')) {
                $table->dropColumn('show_qr');
            }
        });
    }
};
