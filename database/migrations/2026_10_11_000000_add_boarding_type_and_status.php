<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kebijakan boarding satuan pendidikan:
 *  - `schools.boarding_type`: boarding | non_boarding | both.
 *  - `students.boarding_status`: klasifikasi santri (boarding/non-boarding),
 *    dipakai saat satuan pendidikan bertipe `both` agar data jelas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('schools', 'boarding_type')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->string('boarding_type', 20)->default('non_boarding')->after('school_status')
                    ->comment('boarding|non_boarding|both — menentukan keterkaitan modul asrama');
            });
        }

        if (! Schema::hasColumn('students', 'boarding_status')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('boarding_status', 20)->nullable()->after('residence_type')
                    ->comment('boarding|non_boarding — diisi saat satuan pendidikan tipe both');
                $table->index(['school_id', 'boarding_status'], 'students_school_boarding_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('schools', 'boarding_type')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->dropColumn('boarding_type');
            });
        }

        if (Schema::hasColumn('students', 'boarding_status')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropIndex('students_school_boarding_idx');
                $table->dropColumn('boarding_status');
            });
        }
    }
};
