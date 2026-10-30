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
            if (! Schema::hasColumn('rapor_distribution_settings', 'ttd_ksp_margin')) {
                $table->decimal('ttd_ksp_margin', 4, 1)->default(2);
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'ttd_wali_margin')) {
                $table->decimal('ttd_wali_margin', 4, 1)->default(2);
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'stamp_margin')) {
                $table->decimal('stamp_margin', 4, 1)->default(2);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('rapor_distribution_settings')) {
            return;
        }

        Schema::table('rapor_distribution_settings', function (Blueprint $table) {
            foreach (['ttd_ksp_margin', 'ttd_wali_margin', 'stamp_margin'] as $column) {
                if (Schema::hasColumn('rapor_distribution_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
