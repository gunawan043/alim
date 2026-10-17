<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_mutations_out', function (Blueprint $table) {
            if (! Schema::hasColumn('student_mutations_out', 'out_type')) {
                $table->string('out_type', 20)->default('mutation')->after('status');
            }
            if (! Schema::hasColumn('student_mutations_out', 'graduation_year')) {
                $table->year('graduation_year')->nullable()->after('out_type');
            }
            if (! Schema::hasColumn('student_mutations_out', 'graduation_certificate_number')) {
                $table->string('graduation_certificate_number', 50)->nullable()->after('graduation_year');
            }
            if (! Schema::hasColumn('student_mutations_out', 'graduation_school_name')) {
                $table->string('graduation_school_name', 255)->nullable()->after('graduation_certificate_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_mutations_out', function (Blueprint $table) {
            foreach (['out_type', 'graduation_year', 'graduation_certificate_number', 'graduation_school_name'] as $col) {
                if (Schema::hasColumn('student_mutations_out', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
