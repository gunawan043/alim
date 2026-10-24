<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rapor_distribution_settings')
            && ! Schema::hasColumn('rapor_distribution_settings', 'variant_layouts')) {
            Schema::table('rapor_distribution_settings', function (Blueprint $table) {
                $table->json('variant_layouts')->nullable()->after('paper_size');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rapor_distribution_settings')
            && Schema::hasColumn('rapor_distribution_settings', 'variant_layouts')) {
            Schema::table('rapor_distribution_settings', function (Blueprint $table) {
                $table->dropColumn('variant_layouts');
            });
        }
    }
};
