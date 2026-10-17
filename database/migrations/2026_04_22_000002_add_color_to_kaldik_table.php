<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('kaldik', 'color')) {
            Schema::table('kaldik', function (Blueprint $table) {
                $table->string('color', 30)->nullable()->after('type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('kaldik', 'color')) {
            Schema::table('kaldik', function (Blueprint $table) {
                $table->dropColumn('color');
            });
        }
    }
};
