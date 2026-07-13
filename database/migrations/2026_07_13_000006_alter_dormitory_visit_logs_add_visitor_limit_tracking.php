<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dormitory_visit_logs', function (Blueprint $table) {
            $table->boolean('is_special_permission')->default(false)
                ->after('status')
                ->comment('Bypasses quota; recorded in Boarding Timeline.');
            $table->string('special_reason')->nullable()
                ->after('is_special_permission')
                ->comment('Why this visit was treated as special permission.');

            $table->index('student_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('dormitory_visit_logs', function (Blueprint $table) {
            $table->dropColumn('is_special_permission', 'special_reason');
        });
    }
};
