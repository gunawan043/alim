<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('soal', 'jenis_asesmen')) {
            return;
        }

        Schema::table('soal', function (Blueprint $table) {
            $table->string('jenis_asesmen', 20)->default('harian')->after('status');
            $table->index(['dibuat_oleh', 'jenis_asesmen'], 'soal_owner_jenis_asesmen_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('soal', 'jenis_asesmen')) {
            return;
        }

        Schema::table('soal', function (Blueprint $table) {
            $table->dropIndex('soal_owner_jenis_asesmen_idx');
            $table->dropColumn('jenis_asesmen');
        });
    }
};
