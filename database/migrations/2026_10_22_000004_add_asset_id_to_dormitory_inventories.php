<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dormitory_inventories', function (Blueprint $table) {
            if (! Schema::hasColumn('dormitory_inventories', 'asset_id')) {
                $table->uuid('asset_id')->nullable()->after('category_id');
                $table->foreign('asset_id')->references('id')->on('assets')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('dormitory_inventories', function (Blueprint $table) {
            if (Schema::hasColumn('dormitory_inventories', 'asset_id')) {
                $table->dropConstrainedForeignId('asset_id');
            }
        });
    }
};
