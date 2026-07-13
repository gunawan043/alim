<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dormitory_permits', function (Blueprint $table) {
            $table->boolean('is_special_permission')->default(false)
                ->after('status')
                ->comment('Bypasses quota; recorded in Boarding Timeline.');
            $table->string('special_reason')->nullable()
                ->after('is_special_permission')
                ->comment('Why this was treated as special permission.');

            // Add indexes for quota counting queries
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::table('dormitory_permits', function (Blueprint $table) {
            $table->dropColumn('is_special_permission', 'special_reason');
        });
    }
};
