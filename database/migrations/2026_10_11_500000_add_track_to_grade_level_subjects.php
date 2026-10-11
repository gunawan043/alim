<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Peminatan pada alokasi jam per tingkat (SMA: BAHASA / IPA).
 * track null = berlaku semua jalur.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('grade_level_subjects', 'track')) {
            Schema::table('grade_level_subjects', function (Blueprint $table) {
                $table->string('track', 20)->nullable()->after('allocation_hours');
                $table->index(['grade_level_id', 'subject_id', 'track'], 'gls_grade_subject_track_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('grade_level_subjects', 'track')) {
            Schema::table('grade_level_subjects', function (Blueprint $table) {
                $table->dropIndex('gls_grade_subject_track_idx');
                $table->dropColumn('track');
            });
        }
    }
};
