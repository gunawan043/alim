<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NISN boleh kosong: santri pindahan sering belum membawa NISN saat
 * didaftarkan lewat PD Masuk. NISN dilengkapi pada tahap pelengkapan data.
 * Index unik tetap berlaku — MySQL mengizinkan banyak nilai NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('nisn', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('nisn', 20)->nullable(false)->change();
        });
    }
};
