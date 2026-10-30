<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dormitory_posts', function (Blueprint $table) {
            $table->string('banner_path', 500)->nullable()->after('content');
        });

        Schema::table('dormitory_emergency_broadcasts', function (Blueprint $table) {
            $table->string('banner_path', 500)->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('dormitory_posts', function (Blueprint $table) {
            $table->dropColumn('banner_path');
        });

        Schema::table('dormitory_emergency_broadcasts', function (Blueprint $table) {
            $table->dropColumn('banner_path');
        });
    }
};
