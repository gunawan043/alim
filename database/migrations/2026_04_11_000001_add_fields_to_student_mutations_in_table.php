<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_mutations_in', function (Blueprint $table) {
            // Alamat lengkap
            if (! Schema::hasColumn('student_mutations_in', 'student_rt')) {
                $table->string('student_rt', 10)->nullable()->after('student_address');
            }
            if (! Schema::hasColumn('student_mutations_in', 'student_rw')) {
                $table->string('student_rw', 10)->nullable()->after('student_rt');
            }
            if (! Schema::hasColumn('student_mutations_in', 'student_hamlet')) {
                $table->string('student_hamlet', 100)->nullable()->after('student_rw');
            }
            if (! Schema::hasColumn('student_mutations_in', 'student_postal_code')) {
                $table->string('student_postal_code', 10)->nullable()->after('student_hamlet');
            }
            if (! Schema::hasColumn('student_mutations_in', 'student_province_code')) {
                $table->string('student_province_code', 10)->nullable()->after('student_postal_code');
            }
            if (! Schema::hasColumn('student_mutations_in', 'student_city_code')) {
                $table->string('student_city_code', 10)->nullable()->after('student_province_code');
            }
            if (! Schema::hasColumn('student_mutations_in', 'student_district_code')) {
                $table->string('student_district_code', 10)->nullable()->after('student_city_code');
            }
            if (! Schema::hasColumn('student_mutations_in', 'student_village_code')) {
                $table->string('student_village_code', 10)->nullable()->after('student_district_code');
            }

            // Data Ayah
            if (! Schema::hasColumn('student_mutations_in', 'father_name')) {
                $table->string('father_name', 255)->nullable()->after('parent_phone');
            }
            if (! Schema::hasColumn('student_mutations_in', 'father_birth_year')) {
                $table->integer('father_birth_year')->nullable()->after('father_name');
            }
            if (! Schema::hasColumn('student_mutations_in', 'father_education')) {
                $table->string('father_education', 20)->nullable()->after('father_birth_year');
            }
            if (! Schema::hasColumn('student_mutations_in', 'father_occupation')) {
                $table->string('father_occupation', 100)->nullable()->after('father_education');
            }
            if (! Schema::hasColumn('student_mutations_in', 'father_nik')) {
                $table->string('father_nik', 30)->nullable()->after('father_occupation');
            }
            if (! Schema::hasColumn('student_mutations_in', 'father_income')) {
                $table->decimal('father_income', 15, 2)->nullable()->after('father_nik');
            }

            // Data Ibu
            if (! Schema::hasColumn('student_mutations_in', 'mother_name')) {
                $table->string('mother_name', 255)->nullable()->after('father_income');
            }
            if (! Schema::hasColumn('student_mutations_in', 'mother_birth_year')) {
                $table->integer('mother_birth_year')->nullable()->after('mother_name');
            }
            if (! Schema::hasColumn('student_mutations_in', 'mother_education')) {
                $table->string('mother_education', 20)->nullable()->after('mother_birth_year');
            }
            if (! Schema::hasColumn('student_mutations_in', 'mother_occupation')) {
                $table->string('mother_occupation', 100)->nullable()->after('mother_education');
            }
            if (! Schema::hasColumn('student_mutations_in', 'mother_nik')) {
                $table->string('mother_nik', 30)->nullable()->after('mother_occupation');
            }
            if (! Schema::hasColumn('student_mutations_in', 'mother_income')) {
                $table->decimal('mother_income', 15, 2)->nullable()->after('mother_nik');
            }

            // Data Wali
            if (! Schema::hasColumn('student_mutations_in', 'guardian_name')) {
                $table->string('guardian_name', 255)->nullable()->after('mother_income');
            }
            if (! Schema::hasColumn('student_mutations_in', 'guardian_birth_year')) {
                $table->integer('guardian_birth_year')->nullable()->after('guardian_name');
            }
            if (! Schema::hasColumn('student_mutations_in', 'guardian_education')) {
                $table->string('guardian_education', 20)->nullable()->after('guardian_birth_year');
            }
            if (! Schema::hasColumn('student_mutations_in', 'guardian_occupation')) {
                $table->string('guardian_occupation', 100)->nullable()->after('guardian_education');
            }
            if (! Schema::hasColumn('student_mutations_in', 'guardian_nik')) {
                $table->string('guardian_nik', 30)->nullable()->after('guardian_occupation');
            }
            if (! Schema::hasColumn('student_mutations_in', 'guardian_income')) {
                $table->decimal('guardian_income', 15, 2)->nullable()->after('guardian_nik');
            }

            // student_id jadi nullable
            $table->foreignUuid('student_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('student_mutations_in', function (Blueprint $table) {
            $columns = [
                'student_rt', 'student_rw', 'student_hamlet', 'student_postal_code',
                'student_province_code', 'student_city_code', 'student_district_code', 'student_village_code',
                'father_name', 'father_birth_year', 'father_education', 'father_occupation', 'father_nik', 'father_income',
                'mother_name', 'mother_birth_year', 'mother_education', 'mother_occupation', 'mother_nik', 'mother_income',
                'guardian_name', 'guardian_birth_year', 'guardian_education', 'guardian_occupation', 'guardian_nik', 'guardian_income',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('student_mutations_in', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
