<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old (broken) FKs if they exist
        DB::statement('ALTER TABLE asset_rooms DROP FOREIGN KEY IF EXISTS asset_rooms_school_id_foreign');
        DB::statement('ALTER TABLE asset_rooms DROP FOREIGN KEY IF EXISTS asset_rooms_building_id_foreign');

        // Change school_id → NOT NULL (idempotent, already in this state)
        Schema::table('asset_rooms', function (Blueprint $table) {
            $table->uuid('school_id')->nullable(false)->change();
        });

        // Change building_id → nullable
        Schema::table('asset_rooms', function (Blueprint $table) {
            $table->uuid('building_id')->nullable()->change();
        });

        // Add FK for school_id (NOT NULL → use CASCADE, not SET NULL)
        Schema::table('asset_rooms', function (Blueprint $table) {
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
        });

        // Add FK for building_id with cascadeOnDelete
        Schema::table('asset_rooms', function (Blueprint $table) {
            $table->foreign('building_id')->references('id')->on('asset_buildings')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE asset_rooms DROP FOREIGN KEY IF EXISTS asset_rooms_school_id_foreign');
        DB::statement('ALTER TABLE asset_rooms DROP FOREIGN KEY IF EXISTS asset_rooms_building_id_foreign');

        Schema::table('asset_rooms', function (Blueprint $table) {
            $table->uuid('school_id')->nullable()->change();
            $table->uuid('building_id')->nullable(false)->change();
        });

        Schema::table('asset_rooms', function (Blueprint $table) {
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('building_id')->references('id')->on('asset_buildings')->cascadeOnDelete();
        });
    }
};
