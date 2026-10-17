<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_units', function (Blueprint $table) {
            $table->uuid('divisi_id')->nullable();
            $table->foreign('divisi_id')->references('id')->on('divisis')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('work_units', function (Blueprint $table) {
            $table->dropForeign(['divisi_id']);
            $table->dropColumn('divisi_id');
        });
    }
};
