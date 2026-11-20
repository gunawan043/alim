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
            if (! Schema::hasColumn('rapor_distribution_settings', 'table_line_height')) {
                $table->decimal('table_line_height', 3, 2)->default(1.15);
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'table_row_padding')) {
                $table->decimal('table_row_padding', 4, 2)->default(0.50);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('rapor_distribution_settings')) {
            return;
        }

        Schema::table('rapor_distribution_settings', function (Blueprint $table) {
            foreach (['table_line_height', 'table_row_padding'] as $column) {
                if (Schema::hasColumn('rapor_distribution_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
