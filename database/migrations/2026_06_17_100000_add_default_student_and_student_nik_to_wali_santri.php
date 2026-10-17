<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add missing columns to wali_santri for admin-created students.
     *
     * When an admin/operator creates a Student (via StudentController wizard),
     * the related WaliSantri is NOT auto-created. This migration adds
     * student_nik (for quick lookup from wali to student) and default_wali_id
     * (pointer to wali's user_id so the wali app knows its default student
     * without scanning for the primary link).
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        Schema::table('wali_santri', function (Blueprint $table) {
            $table->string('student_nik', 20)->nullable()->after('student_id')
                ->comment('NIK student — synced from students.nik on create/update');
        });

        Schema::table('wali_santri', function (Blueprint $table) {
            $table->uuid('default_student_id')->nullable()->after('student_id')
                ->comment('Default student for wali login app');
        });

        Schema::table('wali_santri', function (Blueprint $table) {
            $table->foreign('default_student_id')
                ->references('id')->on('students')
                ->nullOnDelete();
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        Schema::table('wali_santri', function (Blueprint $table) {
            $table->dropForeign(['default_student_id']);
            $table->dropColumn('default_student_id');
        });

        Schema::table('wali_santri', function (Blueprint $table) {
            $table->dropColumn('student_nik');
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
};
