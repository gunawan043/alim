<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dormitory_inventories', function (Blueprint $table) {
            // Tambah category_id jika belum ada
            if (! Schema::hasColumn('dormitory_inventories', 'category_id')) {
                $table->uuid('category_id')->nullable();
            }

            // Tambahkan FK ke category_id (jika kolom baru saja ada)
            if (Schema::hasColumn('dormitory_inventories', 'category_id')) {
                $table->foreign('category_id')
                    ->references('id')
                    ->on('asset_categories')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dormitory_inventories', function (Blueprint $table) {
            // Drop FK jika ada
            try {
                $table->dropForeign(['category_id']);
            } catch (\Throwable $e) {
                // ignore
            }
            // Drop column jika ada
            if (Schema::hasColumn('dormitory_inventories', 'category_id')) {
                $table->dropColumn('category_id');
            }
        });
    }
};
