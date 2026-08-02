<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('uks_beds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dormitory_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('gender', ['L', 'P'])->comment('UKS Putra (L) atau Putri (P)');
            $table->string('building_or_room')->comment('Nama gedung atau ruang, mis. \"Gedung A\" atau \"Ruang 1\"');
            $table->string('section')->nullable()->comment('Bagian ruangan, mis. \"Ruang A\"');
            $table->string('bed_number')->comment('Nomor tempat tidur, mis. \"A-01\", \"B-03\"');
            $table->enum('status', ['tersedia', 'dipakai', 'perbaikan'])->default('tersedia');
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // Unique: each bed number is unique within a room/section
            $table->unique(['dormitory_id', 'building_or_room', 'section', 'bed_number'], 'uk_dorm_bed_identifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uks_beds');
    }
};
