<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Rename head_nip → head_nupy in student_mutations_in
        if (Schema::hasTable('student_mutations_in') && Schema::hasColumn('student_mutations_in', 'head_nip')) {
            DB::statement('ALTER TABLE student_mutations_in CHANGE head_nip head_nupy VARCHAR(50) NULL');
        }

        // Rename head_nip → head_nupy in student_mutations_out
        if (Schema::hasTable('student_mutations_out') && Schema::hasColumn('student_mutations_out', 'head_nip')) {
            DB::statement('ALTER TABLE student_mutations_out CHANGE head_nip head_nupy VARCHAR(50) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('student_mutations_in') && Schema::hasColumn('student_mutations_in', 'head_nupy')) {
            DB::statement('ALTER TABLE student_mutations_in CHANGE head_nupy head_nip VARCHAR(30) NULL');
        }

        if (Schema::hasTable('student_mutations_out') && Schema::hasColumn('student_mutations_out', 'head_nupy')) {
            DB::statement('ALTER TABLE student_mutations_out CHANGE head_nupy head_nip VARCHAR(30) NULL');
        }
    }
};
