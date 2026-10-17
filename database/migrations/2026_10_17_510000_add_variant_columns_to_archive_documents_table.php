<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ARSIP DOKUMEN — varian rapor: jenis (STS/SAS) × bahasa (Indonesia/Arab).
 * Dipakai untuk idempotensi & filter dokumen arsip.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('archive_documents', 'jenis')) {
            Schema::table('archive_documents', function (Blueprint $table) {
                $table->string('jenis', 10)->nullable()->after('semester')
                    ->comment('sts|sas — jenis dokumen penilaian');
                $table->string('language', 5)->nullable()->after('jenis')
                    ->comment('id|ar — bahasa dokumen');
                $table->index(
                    ['academic_year_id', 'type', 'jenis', 'language'],
                    'archive_docs_variant_idx'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('archive_documents', 'jenis')) {
            Schema::table('archive_documents', function (Blueprint $table) {
                $table->dropIndex('archive_docs_variant_idx');
                $table->dropColumn(['jenis', 'language']);
            });
        }
    }
};
