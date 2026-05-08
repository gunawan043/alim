<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_mutations_out', function (Blueprint $table) {
            $table->string('hijri_date', 100)->nullable()->after('established_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_mutations_out', function (Blueprint $table) {
            $table->dropColumn('hijri_date');
        });
    }
};
