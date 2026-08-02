<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_room_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('room_id');
            $table->uuid('asset_id');
            $table->integer('quantity')->default(1);
            $table->enum('condition', [
                'baik', 'rusak_ringan', 'rusak_sedang', 'rusak_berat',
            ])->default('baik');
            $table->date('last_checked_date')->nullable();
            $table->uuid('checked_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('room_id')->references('id')->on('asset_rooms')->cascadeOnDelete();
            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
            $table->foreign('checked_by')->references('id')->on('users')->nullOnDelete();

            $table->unique(['room_id', 'asset_id'], 'unique_asset_per_room');
            $table->index('room_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_room_items');
    }
};
