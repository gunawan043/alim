<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perluas keunikan alokasi jam agar mendukung PEMINATAN:
 * (grade_level_id, subject_id) → (grade_level_id, subject_id, track).
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('grade_level_subjects', function (Blueprint $table) {
                $table->dropUnique('unique_grade_level_subject');
            });
        } catch (\Throwable) {
            // Index tidak ada / sudah diganti pada driver ini.
        }

        try {
            Schema::table('grade_level_subjects', function (Blueprint $table) {
                $table->unique(['grade_level_id', 'subject_id', 'track'], 'unique_grade_level_subject_track');
            });
        } catch (\Throwable) {
            // Sudah ada.
        }
    }

    public function down(): void
    {
        try {
            Schema::table('grade_level_subjects', function (Blueprint $table) {
                $table->dropUnique('unique_grade_level_subject_track');
                $table->unique(['grade_level_id', 'subject_id'], 'unique_grade_level_subject');
            });
        } catch (\Throwable) {
            // abaikan
        }
    }
};
