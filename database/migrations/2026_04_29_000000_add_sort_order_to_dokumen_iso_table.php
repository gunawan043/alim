<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dokumen_iso', function (Blueprint $table) {
            $table->unsignedTinyInteger('sort_order')->nullable()->default(null)->after('kategori');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('dokumen_iso', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
