<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_iso', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama_dokumen', 255);
            $table->string('prosedur_konsultan', 255)->nullable();
            $table->string('pasal', 100)->nullable();
            $table->string('kode_dokumen', 50)->nullable();
            $table->date('tanggal_berlaku')->nullable();
            $table->string('revisi_ke', 20)->default('0');
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_iso');
    }
};
