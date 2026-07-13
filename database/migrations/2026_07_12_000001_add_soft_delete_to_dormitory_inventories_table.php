<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dormitory_inventories', function (Blueprint $table) {
            $table->softDeletes()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('dormitory_inventories', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
    }
};
