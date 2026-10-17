<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtk_additional_tasks', function (Blueprint $table) {
            $table->foreignUuid('work_unit_id')
                ->nullable()
                ->after('decree_id')
                ->constrained('work_units')
                ->nullOnDelete();
        });

        Schema::table('other_teacher_tasks', function (Blueprint $table) {
            $table->foreignUuid('work_unit_id')
                ->nullable()
                ->after('school_id')
                ->constrained('work_units')
                ->nullOnDelete();

            $table->index(['work_unit_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::table('other_teacher_tasks', function (Blueprint $table) {
            $table->dropForeign(['work_unit_id']);
            $table->dropIndex(['work_unit_id', 'academic_year_id']);
        });

        Schema::table('gtk_additional_tasks', function (Blueprint $table) {
            $table->dropForeign(['work_unit_id']);
        });
    }
};
