<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dormitory_inventories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('room_id');
            $table->uuid('dormitory_id');
            $table->string('item_name', 191);
            $table->string('item_code', 50)->nullable();
            $table->integer('quantity')->default(1);
            $table->enum('condition', ['baik', 'rusak_ringan', 'rusak_berat', 'hilang'])
                  ->default('baik');
            $table->date('last_checked_at')->nullable();
            $table->uuid('checked_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
 
            $table->foreign('room_id')->references('id')->on('dormitory_rooms')->cascadeOnDelete();
            $table->foreign('dormitory_id')->references('id')->on('dormitories')->cascadeOnDelete();
            $table->foreign('checked_by')->references('id')->on('users')->nullOnDelete();
 
            $table->index(['room_id', 'condition']);
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('dormitory_inventories');
    }
};
