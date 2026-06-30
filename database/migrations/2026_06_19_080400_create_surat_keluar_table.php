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
        Schema::create('surat_keluar', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->string('nomor_surat');
            $table->date('tanggal_surat');
            $table->date('tanggal_kirim')->nullable();
            $table->string('tujuan');
            $table->string('perihal');
            $table->string('file_lampiran')->nullable();
            $table->string('sifat')->default('biasa'); // rahasia | biasa | penting
            $table->string('penandatangan')->nullable();
            $table->string('jabatan_penandatangan')->nullable();
            $table->string('status')->default('draft'); // draft | terkirim | dibatalkan
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'tanggal_surat'], 'idx_sk_tgl_surat');
            $table->index(['school_id', 'status'], 'idx_sk_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_keluar');
    }
};