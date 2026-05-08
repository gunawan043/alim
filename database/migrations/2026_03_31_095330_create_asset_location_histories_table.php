<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asset_location_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('asset_id');
            $table->uuid('from_room_id')->nullable();
            $table->uuid('to_room_id')->nullable();
            $table->date('moved_date');
            $table->text('reason')->nullable();
            $table->uuid('moved_by');
            $table->timestamps();

            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
            $table->foreign('from_room_id')->references('id')->on('asset_rooms')->nullOnDelete();
            $table->foreign('to_room_id')->references('id')->on('asset_rooms')->nullOnDelete();
            $table->foreign('moved_by')->references('id')->on('users');

            $table->index(['asset_id', 'moved_date']);
        });
    }

    public function down(): void { Schema::dropIfExists('asset_location_histories'); }
};
