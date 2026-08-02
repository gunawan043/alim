<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_jobs', function (Blueprint $table) {
            $table->json('kategori')->nullable()->after('posisi')->comment('Array UUID jabatan dari tabel jabatan');
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_jobs', function (Blueprint $table) {
            $table->dropColumn('kategori');
        });
    }
};
