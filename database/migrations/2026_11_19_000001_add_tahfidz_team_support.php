<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dukungan TIM TAHFIDZ pada penjadwalan.
 *
 * 1. `jadwal_kbms.teacher_id` dibuat nullable: satu baris blok tahfidz
 *    mewakili satu tim guru, bukan satu orang.
 * 2. `tahfidz_groups.grade_level_id`: halaqah boleh lintas rombel, tetapi
 *    hanya dalam satu tingkat.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('jadwal_kbms') && Schema::hasColumn('jadwal_kbms', 'teacher_id')) {
            Schema::table('jadwal_kbms', function (Blueprint $table) {
                $table->uuid('teacher_id')->nullable()->change();
            });
        }

        if (Schema::hasTable('tahfidz_groups') && ! Schema::hasColumn('tahfidz_groups', 'grade_level_id')) {
            Schema::table('tahfidz_groups', function (Blueprint $table) {
                $table->uuid('grade_level_id')->nullable()->after('study_group_id');
                $table->index('grade_level_id', 'tahfidz_groups_grade_level_id_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tahfidz_groups') && Schema::hasColumn('tahfidz_groups', 'grade_level_id')) {
            Schema::table('tahfidz_groups', function (Blueprint $table) {
                $table->dropIndex('tahfidz_groups_grade_level_id_index');
                $table->dropColumn('grade_level_id');
            });
        }

        // Kembalikan NOT NULL hanya bila tidak ada baris blok tahfidz tersisa.
        if (Schema::hasTable('jadwal_kbms') && Schema::hasColumn('jadwal_kbms', 'teacher_id')) {
            $hasNulls = DB::table('jadwal_kbms')->whereNull('teacher_id')->exists();

            if (! $hasNulls) {
                Schema::table('jadwal_kbms', function (Blueprint $table) {
                    $table->uuid('teacher_id')->nullable(false)->change();
                });
            }
        }
    }
};
