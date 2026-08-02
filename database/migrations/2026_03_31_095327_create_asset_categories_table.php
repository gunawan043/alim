<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable()
                ->comment('NULL = kategori induk (root)');
            $table->string('code', 20)->unique()
                ->comment('Kode golongan, misal: 02, 02.01, 02.01.03');
            $table->string('name', 100);
            $table->enum('asset_type', ['tidak_bergerak', 'bergerak', 'habis_pakai'])
                ->default('bergerak');
            $table->integer('depreciation_years')->nullable()
                ->comment('Masa manfaat aset dalam tahun untuk perhitungan penyusutan');
            $table->text('description')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();

            $table->foreign('parent_id')
                ->references('id')->on('asset_categories')->nullOnDelete();

            $table->index(['parent_id', 'is_active']);
            $table->index('asset_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_categories');
    }
};
