<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sumber mapel:
 *  - pondok    : struktur kurikulum pondok (jam TERKUNCI; satuan hanya boleh ganti nama)
 *  - pemadatan : mapel tambahan yang DIATUR SENDIRI oleh satuan pendidikan
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('subjects', 'source')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->string('source', 20)->default('pondok')->after('category');
                $table->index(['school_id', 'source'], 'subjects_school_source_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('subjects', 'source')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropIndex('subjects_school_source_idx');
                $table->dropColumn('source');
            });
        }
    }
};
