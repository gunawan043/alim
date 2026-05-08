<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_mutations_in', function (Blueprint $table) {
            if (!Schema::hasColumn('student_mutations_in', 'hijri_date')) {
                $table->string('hijri_date', 100)->nullable()->after('established_date');
            }
            if (!Schema::hasColumn('student_mutations_in', 'student_religion')) {
                $table->string('student_religion', 50)->default('Islam')->after('student_gender');
            }
            if (!Schema::hasColumn('student_mutations_in', 'student_previous_class')) {
                $table->string('student_previous_class', 50)->nullable()->after('student_previous_school');
            }
            if (!Schema::hasColumn('student_mutations_in', 'accepted_class')) {
                $table->string('accepted_class', 50)->nullable()->after('parent_phone');
            }
            if (!Schema::hasColumn('student_mutations_in', 'accepted_semester')) {
                $table->string('accepted_semester', 50)->nullable()->after('accepted_class');
            }
            if (!Schema::hasColumn('student_mutations_in', 'accepted_academic_year')) {
                $table->string('accepted_academic_year', 100)->nullable()->after('accepted_semester');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_mutations_in', function (Blueprint $table) {
            foreach (['hijri_date','student_religion','student_previous_class','accepted_class','accepted_semester','accepted_academic_year'] as $col) {
                if (Schema::hasColumn('student_mutations_in', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
