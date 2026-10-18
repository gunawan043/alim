<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('asset_categories', 'inventory_group')) {
                $table->string('inventory_group', 100)->nullable()->after('name')
                    ->comment('Golongan Buku Induk: Elektronik & IT, Furniture, dst.');
            }
        });
    }

    public function down(): void
    {
        Schema::table('asset_categories', function (Blueprint $table) {
            $table->dropColumn('inventory_group');
        });
    }
};
