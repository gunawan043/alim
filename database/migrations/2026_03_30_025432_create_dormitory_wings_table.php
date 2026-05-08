<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dormitory_wings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('dormitory_id');
            $table->string('code', 20);
            $table->string('name', 100);
            $table->tinyInteger('floor')->default(1)->comment('Nomor lantai');
            $table->enum('gender', ['putra', 'putri']);
            $table->integer('capacity')->default(0);
            $table->integer('total_rooms')->default(0);
            $table->uuid('supervisor_id')->nullable()->comment('Pengasuh / musyrif blok');
            $table->tinyInteger('is_active')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
 
            $table->foreign('dormitory_id')->references('id')->on('dormitories')->cascadeOnDelete();
            $table->foreign('supervisor_id')->references('id')->on('users')->nullOnDelete();
 
            $table->unique(['dormitory_id', 'code'], 'unique_wing_code_per_dormitory');
            $table->index('dormitory_id');
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('dormitory_wings');
    }
};
