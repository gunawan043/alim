<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// =============================================================================
// 1. create_tahfidz_packages_table.php
// Paket program tahfidz. Contoh: Reguler, Intensif, Tahfidz Plus.
// =============================================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahfidz_packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_unit_id');
            $table->string('name', 100);
            $table->string('code', 20)->nullable();
            $table->text('description')->nullable();
            $table->integer('total_target_juz')->default(30);
            $table->integer('total_target_halaman')->nullable();
            $table->integer('duration_semesters')->nullable()
                ->comment('Lama paket dalam semester');
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();

            $table->foreign('work_unit_id')->references('id')->on('work_units')->cascadeOnDelete();
            $table->index(['work_unit_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_packages');
    }
};
