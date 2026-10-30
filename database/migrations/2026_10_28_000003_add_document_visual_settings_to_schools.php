<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preferensi tampilan visual dokumen per satuan pendidikan.
 *
 * Struktur JSON:
 * {
 *   "mutasi": {"show_ttd_ksp": true, "show_stamp": true},
 *   "sk": {"show_ttd_ksp": true, "show_stamp": true}
 * }
 *
 * Rapor memiliki pengaturan tata letak sendiri di rapor_distribution_settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('schools', 'document_visual_settings')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->json('document_visual_settings')->nullable()->after('stamp_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('schools', 'document_visual_settings')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->dropColumn('document_visual_settings');
            });
        }
    }
};
