<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->enum('school_gender', ['putra', 'putri'])
                  ->default('putra')
                  ->after('school_level')
                  ->comment('putra = sekolah laki-laki, putri = sekolah perempuan');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('school_gender');
        });
    }
};
