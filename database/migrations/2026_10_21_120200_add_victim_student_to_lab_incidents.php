<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_incidents', function (Blueprint $table) {
            if (! Schema::hasColumn('lab_incidents', 'victim_student_id')) {
                $table->foreignUuid('victim_student_id')->nullable()->after('victim_name')
                    ->constrained('students')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('lab_incidents', function (Blueprint $table) {
            if (Schema::hasColumn('lab_incidents', 'victim_student_id')) {
                $table->dropForeign(['victim_student_id']);
                $table->dropColumn('victim_student_id');
            }
        });
    }
};
