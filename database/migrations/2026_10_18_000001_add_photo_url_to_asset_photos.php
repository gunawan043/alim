<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('asset_photos', 'photo_url')) {
            Schema::table('asset_photos', function (Blueprint $table) {
                $table->text('photo_url')->nullable()->after('photo_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('asset_photos', 'photo_url')) {
            Schema::table('asset_photos', function (Blueprint $table) {
                $table->dropColumn('photo_url');
            });
        }
    }
};
