<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('violation_points', function (Blueprint $table) {
            $table->foreignUuid('academic_year_id')
                ->nullable()
                ->constrained('academic_years')
                ->cascadeOnDelete()
                ->after('study_group_id');

            $table->index('academic_year_id');
        });
    }

    public function down(): void
    {
        Schema::table('violation_points', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropColumn('academic_year_id');
        });
    }
};
