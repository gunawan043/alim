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
            if (! Schema::hasColumn('rapor_distribution_settings', 'margin_top')) {
                $table->decimal('margin_top', 4, 1)->default(10);
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'margin_right')) {
                $table->decimal('margin_right', 4, 1)->default(15);
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'margin_bottom')) {
                $table->decimal('margin_bottom', 4, 1)->default(15);
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'margin_left')) {
                $table->decimal('margin_left', 4, 1)->default(15);
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'font_size')) {
                $table->decimal('font_size', 4, 1)->default(11);
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'title_font_size')) {
                $table->decimal('title_font_size', 4, 1)->default(14);
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'table_font_size')) {
                $table->decimal('table_font_size', 4, 1)->default(10);
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'info_font_size')) {
                $table->decimal('info_font_size', 4, 1)->default(10);
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'footer_font_size')) {
                $table->decimal('footer_font_size', 4, 1)->default(8);
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'line_height')) {
                $table->decimal('line_height', 3, 2)->default(1.40);
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'paper_size')) {
                $table->string('paper_size', 10)->default('a4');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('rapor_distribution_settings')) {
            return;
        }

        Schema::table('rapor_distribution_settings', function (Blueprint $table) {
            foreach ([
                'margin_top', 'margin_right', 'margin_bottom', 'margin_left',
                'font_size', 'title_font_size', 'table_font_size', 'info_font_size',
                'footer_font_size', 'line_height', 'paper_size',
            ] as $column) {
                if (Schema::hasColumn('rapor_distribution_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
