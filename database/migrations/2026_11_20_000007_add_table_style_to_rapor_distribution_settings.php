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
            if (! Schema::hasColumn('rapor_distribution_settings', 'table_border_width')) {
                $table->decimal('table_border_width', 3, 2)->default(0.30);
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'bold_table_header')) {
                $table->boolean('bold_table_header')->default(true);
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'bold_subject')) {
                $table->boolean('bold_subject')->default(true);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('rapor_distribution_settings')) {
            return;
        }

        Schema::table('rapor_distribution_settings', function (Blueprint $table) {
            foreach (['table_border_width', 'bold_table_header', 'bold_subject'] as $column) {
                if (Schema::hasColumn('rapor_distribution_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
