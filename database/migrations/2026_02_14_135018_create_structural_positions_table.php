<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structural_positions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->string('code', 50)->unique(); // Kode jabatan
            $table->string('name', 100); // Nama jabatan
            $table->string('level', 50); // Tingkat: yayasan, pondok, madrasah
            $table->integer('hierarchy_level'); // Level hierarki (1 tertinggi)
            
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            $table->index('level');
            $table->index('hierarchy_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('structural_positions');
    }
};