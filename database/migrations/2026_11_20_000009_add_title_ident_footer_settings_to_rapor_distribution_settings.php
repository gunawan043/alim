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
            if (! Schema::hasColumn('rapor_distribution_settings', 'title_text')) {
                $table->string('title_text', 255)->nullable();
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'title_ar_text')) {
                $table->string('title_ar_text', 255)->nullable();
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'title_id_text')) {
                $table->string('title_id_text', 255)->nullable();
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'title_gap')) {
                $table->decimal('title_gap', 4, 2)->default(0.50);
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'title_line_height')) {
                $table->decimal('title_line_height', 3, 2)->default(1.20);
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'title_margin_top')) {
                $table->decimal('title_margin_top', 4, 2)->default(2.00);
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'title_margin_bottom')) {
                $table->decimal('title_margin_bottom', 4, 2)->default(2.50);
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'ident_line_height')) {
                $table->decimal('ident_line_height', 3, 2)->default(1.05);
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'ident_row_padding')) {
                $table->decimal('ident_row_padding', 4, 2)->default(0.15);
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'footer_border')) {
                $table->boolean('footer_border')->default(false);
            }
            if (! Schema::hasColumn('rapor_distribution_settings', 'footer_padding')) {
                $table->decimal('footer_padding', 4, 2)->default(2.00);
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
                'title_text', 'title_ar_text', 'title_id_text', 'title_gap',
                'title_line_height', 'title_margin_top', 'title_margin_bottom',
                'ident_line_height', 'ident_row_padding', 'footer_border', 'footer_padding',
            ] as $column) {
                if (Schema::hasColumn('rapor_distribution_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
