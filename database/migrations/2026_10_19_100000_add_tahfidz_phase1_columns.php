<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tahfidz_groups', 'study_group_id')) {
            Schema::table('tahfidz_groups', function (Blueprint $table) {
                $table->uuid('study_group_id')->nullable()->after('school_id')->index();
            });
        }

        if (! Schema::hasColumn('tahfidz_packages', 'lines_per_page')) {
            Schema::table('tahfidz_packages', function (Blueprint $table) {
                $table->unsignedSmallInteger('lines_per_page')->default(15)->after('total_target_halaman');
                $table->unsignedSmallInteger('total_pages')->default(604)->after('lines_per_page');
            });
        }

        if (! Schema::hasColumn('tahfidz_curriculums', 'lines_per_page')) {
            Schema::table('tahfidz_curriculums', function (Blueprint $table) {
                $table->unsignedSmallInteger('lines_per_page')->nullable()->after('target_hadits');
            });
        }

        if (! Schema::hasColumn('tahfidz_setorans', 'verification_status')) {
            Schema::table('tahfidz_setorans', function (Blueprint $table) {
                $table->string('verification_status', 20)->default('pending')->index()->after('status');
                $table->uuid('verified_by')->nullable()->after('verification_status');
                $table->timestamp('verified_at')->nullable()->after('verified_by');
                $table->text('verification_notes')->nullable()->after('verified_at');
            });
        }

        if (! Schema::hasColumn('students', 'origin_school_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->uuid('origin_school_id')->nullable()->index()->after('school_id');
                $table->string('origin_level', 10)->nullable()->after('origin_school_id');
                $table->unsignedSmallInteger('origin_graduation_year')->nullable()->after('origin_level');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'origin_school_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn(['origin_school_id', 'origin_level', 'origin_graduation_year']);
            });
        }

        if (Schema::hasColumn('tahfidz_setorans', 'verification_status')) {
            Schema::table('tahfidz_setorans', function (Blueprint $table) {
                $table->dropColumn(['verification_status', 'verified_by', 'verified_at', 'verification_notes']);
            });
        }

        if (Schema::hasColumn('tahfidz_curriculums', 'lines_per_page')) {
            Schema::table('tahfidz_curriculums', function (Blueprint $table) {
                $table->dropColumn('lines_per_page');
            });
        }

        if (Schema::hasColumn('tahfidz_packages', 'lines_per_page')) {
            Schema::table('tahfidz_packages', function (Blueprint $table) {
                $table->dropColumn(['lines_per_page', 'total_pages']);
            });
        }

        if (Schema::hasColumn('tahfidz_groups', 'study_group_id')) {
            Schema::table('tahfidz_groups', function (Blueprint $table) {
                $table->dropColumn('study_group_id');
            });
        }
    }
};
