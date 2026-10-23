<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bank_soal', 'bahasa')) {
            Schema::table('bank_soal', function (Blueprint $table) {
                $table->string('bahasa')->default('latin')->after('jenis_soal');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bank_soal', 'bahasa')) {
            Schema::table('bank_soal', function (Blueprint $table) {
                $table->dropColumn('bahasa');
            });
        }
    }
};
