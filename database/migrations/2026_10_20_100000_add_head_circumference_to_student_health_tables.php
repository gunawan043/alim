<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_health_records') && ! Schema::hasColumn('student_health_records', 'head_circumference_cm')) {
            Schema::table('student_health_records', function (Blueprint $table) {
                $table->unsignedSmallInteger('head_circumference_cm')
                    ->nullable()
                    ->after('weight_kg')
                    ->comment('Lingkar kepala dalam cm');
            });
        }

        if (Schema::hasTable('student_health_metrics') && ! Schema::hasColumn('student_health_metrics', 'head_circumference_cm')) {
            Schema::table('student_health_metrics', function (Blueprint $table) {
                $table->unsignedSmallInteger('head_circumference_cm')
                    ->nullable()
                    ->after('weight_kg')
                    ->comment('Lingkar kepala dalam cm');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('student_health_records', 'head_circumference_cm')) {
            Schema::table('student_health_records', function (Blueprint $table) {
                $table->dropColumn('head_circumference_cm');
            });
        }

        if (Schema::hasColumn('student_health_metrics', 'head_circumference_cm')) {
            Schema::table('student_health_metrics', function (Blueprint $table) {
                $table->dropColumn('head_circumference_cm');
            });
        }
    }
};
