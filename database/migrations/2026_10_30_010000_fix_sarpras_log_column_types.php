<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perbaikan tipe kolom pada tabel logistik Sarpras agar konsisten dengan
 * primary key UUID:
 *
 * 1. `asset_event_logs.actor_id` semula unsignedBigInteger padahal
 *    `users.id` bertipe char(36). Akibatnya setiap penulisan timeline
 *    dengan aktor gagal (datatype mismatch) dan diam-diam ditelan oleh
 *    try/catch pada AssetEventLogger — tabel selalu kosong di produksi.
 * 2. `stock_opname_items.observed_status` semula NOT NULL padahal status
 *    observasi baru terisi setelah QR-scan; createSession selalu gagal
 *    NOT NULL constraint.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_event_logs', function (Blueprint $table) {
            if (Schema::hasColumn('asset_event_logs', 'actor_id')) {
                $table->uuid('actor_id')->nullable()->change();
            }
        });

        Schema::table('stock_opname_items', function (Blueprint $table) {
            if (Schema::hasColumn('stock_opname_items', 'observed_status')) {
                $table->string('observed_status')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('asset_event_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('actor_id')->nullable()->change();
        });

        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->string('observed_status')->nullable(false)->change();
        });
    }
};
