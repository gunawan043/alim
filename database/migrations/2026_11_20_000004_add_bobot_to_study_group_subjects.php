<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bobot mapel untuk rapor SAS.
 *
 * Default kosong → dokumen memakai JP rombel (study_group_subjects.weekly_hours).
 * Nilai terisi menjadi override manual yang bisa berupa desimal (mis. 2,5).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('study_group_subjects') || Schema::hasColumn('study_group_subjects', 'bobot')) {
            return;
        }

        Schema::table('study_group_subjects', function (Blueprint $table) {
            $table->decimal('bobot', 6, 2)->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('study_group_subjects') || ! Schema::hasColumn('study_group_subjects', 'bobot')) {
            return;
        }

        Schema::table('study_group_subjects', function (Blueprint $table) {
            $table->dropColumn('bobot');
        });
    }
};
