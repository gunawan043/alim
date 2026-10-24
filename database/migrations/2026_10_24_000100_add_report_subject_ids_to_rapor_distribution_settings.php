<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rapor_distribution_settings')
            && ! Schema::hasColumn('rapor_distribution_settings', 'report_subject_ids')) {
            Schema::table('rapor_distribution_settings', function (Blueprint $table) {
                $table->json('report_subject_ids')->nullable()->after('notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rapor_distribution_settings')
            && Schema::hasColumn('rapor_distribution_settings', 'report_subject_ids')) {
            Schema::table('rapor_distribution_settings', function (Blueprint $table) {
                $table->dropColumn('report_subject_ids');
            });
        }
    }
};
