<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dormitory_rooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('dormitory_id');
            $table->uuid('wing_id');
            $table->string('code', 20)->unique()->comment('Kode kamar, contoh: A-101');
            $table->string('name', 100)->nullable()->comment('Nama kamar jika ada');
            $table->tinyInteger('floor')->default(1);
            $table->enum('gender', ['putra', 'putri']);
            $table->integer('capacity')->default(0)->comment('Kapasitas maksimal penghuni');
            $table->enum('room_type', ['reguler', 'khusus', 'isolasi', 'musyrif'])->default('reguler');
            $table->text('facility_notes')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
 
            $table->foreign('dormitory_id')->references('id')->on('dormitories')->cascadeOnDelete();
            $table->foreign('wing_id')->references('id')->on('dormitory_wings')->cascadeOnDelete();
 
            $table->index(['dormitory_id', 'wing_id']);
            $table->index(['dormitory_id', 'gender']);
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('dormitory_rooms');
    }
};
