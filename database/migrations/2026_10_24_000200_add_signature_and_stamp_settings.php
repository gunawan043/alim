<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'ttd_path')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('ttd_path', 255)->nullable()->after('avatar');
            });
        }

        if (! Schema::hasTable('rapor_distribution_settings')) {
            return;
        }

        Schema::table('rapor_distribution_settings', function (Blueprint $table) {
            foreach ([
                'show_kop' => ['boolean', true],
                'show_ttd_ksp' => ['boolean', true],
                'show_ttd_wali' => ['boolean', true],
                'show_stamp' => ['boolean', true],
            ] as $column => $definition) {
                if (! Schema::hasColumn('rapor_distribution_settings', $column)) {
                    $table->boolean($column)->default($definition[1]);
                }
            }

            foreach ([
                'kop_height' => 25.0,
                'ttd_ksp_width' => 30.0,
                'ttd_ksp_height' => 15.0,
                'ttd_wali_width' => 30.0,
                'ttd_wali_height' => 15.0,
                'stamp_width' => 30.0,
                'stamp_height' => 30.0,
            ] as $column => $default) {
                if (! Schema::hasColumn('rapor_distribution_settings', $column)) {
                    $table->decimal($column, 5, 1)->default($default);
                }
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('rapor_distribution_settings')) {
            Schema::table('rapor_distribution_settings', function (Blueprint $table) {
                foreach ([
                    'show_kop', 'show_ttd_ksp', 'show_ttd_wali', 'show_stamp',
                    'kop_height', 'ttd_ksp_width', 'ttd_ksp_height',
                    'ttd_wali_width', 'ttd_wali_height', 'stamp_width', 'stamp_height',
                ] as $column) {
                    if (Schema::hasColumn('rapor_distribution_settings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'ttd_path')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('ttd_path');
            });
        }
    }
};
