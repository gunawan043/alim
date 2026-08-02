<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('academic_years', 'school_id')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            // MySQL supports dropping FK by name
            DB::statement('ALTER TABLE academic_years DROP FOREIGN KEY academic_years_school_id_foreign');
        } else {
            // SQLite (and other drivers) only support dropForeign by column array
            Schema::table('academic_years', function (Blueprint $table) {
                $table->dropForeign(['school_id']);
            });
        }

        Schema::table('academic_years', function (Blueprint $table) {
            $table->dropUnique('unique_academic_year');
            $table->dropColumn('school_id');
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
