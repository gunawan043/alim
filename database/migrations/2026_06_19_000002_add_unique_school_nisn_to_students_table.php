<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Composite unique (school_id, nisn) — NISN is per-school (Nomor Induk Siswa Nasional)
            // MySQL allows multiple NULLs in a UNIQUE index, so partial duplicates
            // among students without a NISN are fine.
            $table->unique(['school_id', 'nisn'], 'students_school_id_nisn_unique');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique('students_school_id_nisn_unique');
        });
    }
};
