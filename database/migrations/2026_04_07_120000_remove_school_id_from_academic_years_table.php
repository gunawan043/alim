<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            if (Schema::hasColumn('academic_years', 'school_id')) {
                $table->dropForeign('academic_years_school_id_foreign');
                $table->dropUnique('unique_academic_year');
                $table->dropColumn('school_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            $table->uuid('school_id')->nullable()->after('id');
            $table->unique(['school_id', 'name', 'semester'], 'unique_academic_year');
        });
    }
};
