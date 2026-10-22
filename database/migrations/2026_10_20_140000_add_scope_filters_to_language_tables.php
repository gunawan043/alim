<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('language_vocabulary_periods') && ! Schema::hasColumn('language_vocabulary_periods', 'grade_level_id')) {
            Schema::table('language_vocabulary_periods', function (Blueprint $table) {
                $table->foreignUuid('grade_level_id')->nullable()->after('school_id')->constrained('grade_levels')->nullOnDelete();
                $table->foreignUuid('study_group_id')->nullable()->after('grade_level_id')->constrained('study_groups')->nullOnDelete();
            });
        }

        if (Schema::hasTable('language_monthly_activities') && ! Schema::hasColumn('language_monthly_activities', 'grade_level_id')) {
            Schema::table('language_monthly_activities', function (Blueprint $table) {
                $table->foreignUuid('grade_level_id')->nullable()->after('school_id')->constrained('grade_levels')->nullOnDelete();
                $table->foreignUuid('study_group_id')->nullable()->after('grade_level_id')->constrained('study_groups')->nullOnDelete();
            });
        }

        if (Schema::hasTable('language_daily_logs') && ! Schema::hasColumn('language_daily_logs', 'grade_level_id')) {
            Schema::table('language_daily_logs', function (Blueprint $table) {
                $table->foreignUuid('grade_level_id')->nullable()->after('school_id')->constrained('grade_levels')->nullOnDelete();
                $table->foreignUuid('study_group_id')->nullable()->after('grade_level_id')->constrained('study_groups')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['language_vocabulary_periods', 'language_monthly_activities', 'language_daily_logs'] as $tableName) {
            if (Schema::hasColumn($tableName, 'study_group_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('study_group_id');
                    $table->dropConstrainedForeignId('grade_level_id');
                });
            }
        }
    }
};
