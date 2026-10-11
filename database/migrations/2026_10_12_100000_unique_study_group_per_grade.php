<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nama rombel boleh sama antar tingkat (A, B, C per tingkat):
 * unique lama (school, TA, name) diganti unique (school, TA, grade_level, name).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tambah index baru DULU — FK school_id membutuhkan index pendukung,
        // sehingga unique lama tidak boleh dihapus sebelum penggantinya ada.
        Schema::table('study_groups', function (Blueprint $table) {
            $table->unique(
                ['school_id', 'academic_year_id', 'grade_level_id', 'name'],
                'unique_study_group_grade'
            );
        });

        Schema::table('study_groups', function (Blueprint $table) {
            $table->dropUnique('unique_study_group');
        });
    }

    public function down(): void
    {
        Schema::table('study_groups', function (Blueprint $table) {
            $table->unique(['school_id', 'academic_year_id', 'name'], 'unique_study_group');
        });

        Schema::table('study_groups', function (Blueprint $table) {
            $table->dropUnique('unique_study_group_grade');
        });
    }
};
